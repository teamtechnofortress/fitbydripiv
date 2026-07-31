<?php

namespace App\Http\Controllers;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Http\Requests\FetchOrderPatientInfoRequest;
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
        $validated = $request->validated();

        $email = trim((string) ($validated['email'] ?? ''));
        $phone = trim((string) ($validated['phone'] ?? ''));

        $patient = Patient::query()
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

        if (! $patient) {
            return response()->json([
                'message' => 'Patient not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Patient information fetched successfully.',
            'data' => $this->patientFormPayload($patient),
            'patient' => [
                'id' => $patient->id,
                'first_name' => $patient->first_name,
                'middle_name' => $patient->middle_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'phone' => $patient->phone,
                'birthday' => $patient->birthday,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'ethnicity' => $patient->ethnicity,
                'address' => $patient->address,
                'city' => $patient->city,
                'state' => $patient->state,
                'zip' => $patient->zip,
            ],
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

            $patient = Patient::firstOrNew(['email' => $validated['email']]);
            $patient->fill([
                'first_name' => $validated['firstName'],
                'middle_name' => $validated['middleName'] ?? null,
                'last_name' => $validated['lastName'],
                'phone' => $phone !== '' ? $phone : null,
                'address' => $validated['address'],
                'city' => $validated['city'],
                'state' => $validated['state'],
                'zip' => $validated['zip'],
                'birthday' => $validated['dateOfBirth'],
                'age' => $validated['age'] ?? null,
                'gender' => $validated['gender'] ?? null,
                'ethnicity' => $validated['ethnicity'] ?? null,
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

    private function patientFormPayload(Patient $patient): array
    {
        return [
            'firstName' => $patient->first_name,
            'middleName' => $patient->middle_name,
            'lastName' => $patient->last_name,
            'phone' => $patient->phone,
            'address' => $patient->address,
            'city' => $patient->city,
            'state' => $patient->state,
            'zip' => $patient->zip,
            'email' => $patient->email,
            'dateOfBirth' => $patient->birthday,
            'age' => $patient->age,
            'gender' => $patient->gender,
            'ethnicity' => $patient->ethnicity,
        ];
    }
}
