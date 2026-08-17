<?php

namespace App\Http\Controllers;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Http\Requests\FetchOrderPatientInfoRequest;
use App\Http\Requests\SaveOrderAdditionalPatientInfoRequest;
use App\Http\Requests\SaveOrderPatientInfoRequest;
use App\Models\Order;
use App\Models\Patient;
use App\Services\CheckoutResponseService;
use App\Services\DrNetwork\Assignment\DrNetworkAssignmentService;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use App\Services\IdempotencyService;
use App\Services\OrderJourneyService;
use App\Services\StateCodeResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderPatientInfoController extends Controller
{
    public function __construct(
        private IdempotencyService $idempotencyService,
        private CheckoutResponseService $checkoutResponseService,
        private OrderJourneyService $journeyService,
        private StateCodeResolver $stateCodeResolver,
        private DrNetworkAssignmentService $drNetworkAssignmentService,
        private DrNetworkOrchestrator $drNetworkOrchestrator
    ) {}

    public function show(FetchOrderPatientInfoRequest $request, Order $order): JsonResponse
    {
        $patient = $this->findPatientByContact($request->validated());

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Required patient information fetched successfully.',
            'data' => $this->requiredPatientInfoPayload($patient),
            'patient' => $this->requiredPatientResource($patient),
        ]);
    }

    public function showAdditional(Order $order): JsonResponse
    {
        $order->loadMissing('patient');
        $patient = $order->patient;

        if (! $patient) {
            return response()->json([
                'message' => 'Required patient information must be submitted first.',
                'errors' => [
                    'patient_info' => ['Submit required patient information before fetching additional patient information.'],
                ],
            ], 422);
        }

        return response()->json([
            'message' => 'Additional patient information fetched successfully.',
            'data' => $this->additionalPatientInfoPayload($patient),
            'patient' => $this->additionalPatientResource($patient),
        ]);
    }

    public function store(SaveOrderPatientInfoRequest $request, Order $order): JsonResponse
    {
        $validated = $request->validated();
        $stateCode = $this->stateCodeResolver->resolve($validated['state']);

        if (! $stateCode) {
            return response()->json([
                'message' => 'The selected state is not supported.',
                'errors' => [
                    'state' => ['Please provide a valid US state name or state code.'],
                ],
            ], 422);
        }

        $idempotencyPayload = array_merge($validated, [
            'orderUuid' => $order->order_uuid,
            'stateCode' => $stateCode,
        ]);

        try {
            $result = $this->idempotencyService->handle(
                $request->header('Idempotency-Key'),
                'orders.patient-info',
                $idempotencyPayload,
                fn () => $this->savePatientInfoAndStartJourney($validated, $order, $stateCode)
            );
        } catch (NetworkAssignmentException $e) {
            Log::channel('dr_network')->warning('Patient info saved but Dr Network assignment failed.', [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($result, ($result['already_completed'] ?? false) ? 200 : 201);
    }

    public function storeAdditional(SaveOrderAdditionalPatientInfoRequest $request, Order $order): JsonResponse
    {
        $validated = $request->validated();
        $order->loadMissing(['patient', 'flowRun']);

        if (! $order->patient) {
            return response()->json([
                'message' => 'Required patient information must be submitted first.',
                'errors' => [
                    'patient_info' => ['Submit date of birth, email, phone, state, and gender before additional patient information.'],
                ],
            ], 422);
        }

        $idempotencyPayload = array_merge($validated, [
            'orderUuid' => $order->order_uuid,
            'patientId' => $order->patient_id,
        ]);

        $result = $this->idempotencyService->handle(
            $request->header('Idempotency-Key'),
            'orders.patient-info.additional',
            $idempotencyPayload,
            fn () => $this->saveAdditionalPatientInfo($validated, $order)
        );

        return response()->json($result);
    }

    private function savePatientInfoAndStartJourney(array $validated, Order $order, string $stateCode): array
    {
        $order->loadMissing('flowRun');

        if ($order->patient_id && $order->flowRun) {
            return $this->buildResponse(
                $order->fresh(['flowRun']),
                'Patient information is already completed.',
                true
            );
        }

        $updatedOrder = DB::transaction(function () use ($validated, $order, $stateCode): Order {
            $phone = trim((string) ($validated['phone'] ?? ''));
            $bodyMetrics = $this->bodyMetricsFromRequiredPatientInfo($validated);

            $patient = Patient::firstOrNew(['email' => $validated['email']]);
            $patient->fill([
                'state' => $validated['state'],
                'phone' => $phone !== '' ? $phone : null,
                'birthday' => $validated['dateOfBirth'],
                'gender' => $validated['gender'],
                'height' => $bodyMetrics['height'],
                'weight' => $bodyMetrics['weight'],
                'bmi' => $bodyMetrics['bmi'],
            ]);
            $patient->save();

            if ($order->patient_id !== $patient->id || $order->state_code !== $stateCode) {
                $order->patient_id = $patient->id;
                $order->state_code = $stateCode;
                $order->save();
            }

            Log::info('Order patient information saved.', [
                'order_id' => $order->id,
                'patient_id' => $patient->id,
                'state_code' => $stateCode,
            ]);

            return $order->fresh(['patient', 'flowRun']);
        });

        $assignedOrder = $this->drNetworkAssignmentService->assignRouting($updatedOrder);
        $this->drNetworkOrchestrator->startForOrder($assignedOrder);

        return $this->buildResponse(
            $assignedOrder->fresh(['patient', 'flowRun']),
            'Patient information saved successfully.',
            false
        );
    }

    private function bodyMetricsFromRequiredPatientInfo(array $validated): array
    {
        $feet = (int) $validated['heightFeet'];
        $inches = (int) $validated['heightInches'];
        $weight = (float) $validated['weight'];
        $totalInches = ($feet * 12) + $inches;

        return [
            'height' => $totalInches,
            'weight' => $weight,
            'bmi' => round(($weight * 703) / ($totalInches * $totalInches), 2),
        ];
    }

    private function saveAdditionalPatientInfo(array $validated, Order $order): array
    {
        $updatedOrder = DB::transaction(function () use ($validated, $order): Order {
            $order->loadMissing('patient');
            $patient = $order->patient;

            $patient->fill([
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'],
                'address' => $validated['address'],
                'city' => $validated['city'],
                'zip' => $validated['zip'],
            ]);
            $patient->save();

            Log::info('Additional order patient information saved.', [
                'order_id' => $order->id,
                'patient_id' => $patient->id,
            ]);

            return $order->fresh(['patient', 'flowRun']);
        });

        return $this->buildResponse(
            $updatedOrder,
            'Additional patient information saved successfully.',
            false
        );
    }

    private function buildResponse(Order $order, string $message, bool $alreadyCompleted): array
    {
        $context = $this->checkoutResponseService->buildOrderContext($order);
        $journey = $this->journeyService->build($order);

        return [
            'success' => true,
            'message' => $message,
            'already_completed' => $alreadyCompleted,
            'data' => array_merge($context, [
                'journey' => $journey,
            ]),
        ];
    }

    private function findPatientByContact(array $validated): ?Patient
    {
        $email = trim((string) ($validated['email'] ?? ''));
        $phone = trim((string) ($validated['cell'] ?? ''));

        if ($phone === '') {
            $phone = trim((string) ($validated['phone'] ?? ''));
        }

        $dateOfBirth = Carbon::parse($validated['dateOfBirth'])->toDateString();

        return Patient::query()
            ->whereDate('birthday', $dateOfBirth)
            ->where(function ($query) use ($email, $phone): void {
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }

                if ($phone !== '') {
                    $query
                        ->orWhere('phone', $phone)
                        ->orWhere('cell', $phone);
                }
            })
            ->first();
    }

    private function requiredPatientInfoPayload(Patient $patient): array
    {
        return [
            'firstName' => $patient->first_name,
            'lastName' => $patient->last_name,
            'phone' => $patient->phone,
            'state' => $patient->state,
            'email' => $patient->email,
            'dateOfBirth' => $patient->birthday,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'height' => $patient->height,
            'heightFeet' => $this->heightFeet($patient->height),
            'heightInches' => $this->heightInches($patient->height),
            'weight' => $patient->weight,
            'bmi' => $patient->bmi,
        ];
    }

    private function requiredPatientResource(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'email' => $patient->email,
            'phone' => $patient->phone,
            'birthday' => $patient->birthday,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'state' => $patient->state,
            'height' => $patient->height,
            'heightFeet' => $this->heightFeet($patient->height),
            'heightInches' => $this->heightInches($patient->height),
            'weight' => $patient->weight,
            'bmi' => $patient->bmi,
        ];
    }

    private function additionalPatientInfoPayload(Patient $patient): array
    {
        return [
            'firstName' => $patient->first_name,
            'lastName' => $patient->last_name,
            'address' => $patient->address,
            'city' => $patient->city,
            'zip' => $patient->zip,
        ];
    }

    private function additionalPatientResource(Patient $patient): array
    {
        return [
            'id' => $patient->id,
            'first_name' => $patient->first_name,
            'last_name' => $patient->last_name,
            'address' => $patient->address,
            'city' => $patient->city,
            'zip' => $patient->zip,
        ];
    }

    private function heightFeet(mixed $height): ?int
    {
        if (! is_numeric($height) || (float) $height <= 0) {
            return null;
        }

        return intdiv((int) round((float) $height), 12);
    }

    private function heightInches(mixed $height): ?int
    {
        if (! is_numeric($height) || (float) $height <= 0) {
            return null;
        }

        return (int) round((float) $height) % 12;
    }
}
