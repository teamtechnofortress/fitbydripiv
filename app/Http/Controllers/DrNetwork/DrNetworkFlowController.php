<?php

namespace App\Http\Controllers\DrNetwork;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Exceptions\DrNetwork\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DrNetwork\BookSlotRequest;
use App\Http\Requests\DrNetwork\SaveIntakeAnswerRequest;
use App\Http\Requests\DrNetwork\UploadDocumentRequest;
use App\Models\Order;
use App\Services\DrNetwork\ConsultationManagement\ConsultationSubmissionService;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use App\Services\DrNetwork\DocumentManagement\DocumentRequirementResolver;
use App\Services\DrNetwork\DocumentManagement\DocumentUploadService;
use App\Services\DrNetwork\IntakeQuestions\IntakeAnswerService;
use App\Services\DrNetwork\IntakeQuestions\IntakeQuestionSetResolver;
use App\Services\DrNetwork\ProviderScheduling\ProviderSlotService;
use Illuminate\Http\JsonResponse;

class DrNetworkFlowController extends Controller
{
    public function __construct(
        private DrNetworkOrchestrator $orchestrator,
        private DocumentRequirementResolver $documentRequirementResolver,
        private DocumentUploadService $documentUploadService,
        private IntakeQuestionSetResolver $questionSetResolver,
        private IntakeAnswerService $answerService,
        private ProviderSlotService $slotService,
        private ConsultationSubmissionService $submissionService,
    ) {}

    public function start(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        try {
            $flowRun = $this->orchestrator->startForOrder($order);
        } catch (NetworkAssignmentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'flow_run_id' => $flowRun->id,
            'status' => $flowRun->status,
            'current_step_key' => $flowRun->current_step_key,
        ]);
    }

    public function currentStep(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);
        $order->loadMissing(['flowRun', 'product']);

        $flowRun = $order->flowRun;

        if (! $flowRun) {
            return response()->json(['message' => 'Flow not started.'], 404);
        }

        $payload = [
            'step' => $flowRun->current_step_key,
            'status' => $flowRun->status,
        ];

        if ($flowRun->current_step_key === 'document_upload') {
            $payload['document_requirements'] = $this->documentRequirementResolver->resolve(
                $order->dr_network_id,
                $order->network_flow_key,
                $order->state_code,
                $order->product?->slug
            );
        }

        if ($flowRun->current_step_key === 'intake_questions') {
            $questionSet = $this->questionSetResolver->resolve(
                $order->dr_network_id,
                $order->network_flow_key,
                $order->product?->slug,
                $order->state_code
            );

            $payload['question_set'] = $questionSet;

            if ($questionSet) {
                $flowRun->update([
                    'context' => array_merge($flowRun->context ?? [], [
                        'question_set_id' => $questionSet['set_id'],
                    ]),
                ]);
            }
        }

        return response()->json($payload);
    }

    public function uploadDocument(UploadDocumentRequest $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $result = $this->documentUploadService->store(
            $order,
            $request->file('document'),
            (int) $request->input('document_type_id')
        );

        $flowRun = $order->flowRun?->fresh();

        return response()->json([
            'all_satisfied' => $result['all_satisfied'],
            'satisfied' => $result['satisfied'],
            'unsatisfied' => $result['unsatisfied'],
            'current_step_key' => $flowRun?->current_step_key,
        ]);
    }

    public function saveIntakeAnswer(SaveIntakeAnswerRequest $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $this->answerService->saveAnswer(
            $order,
            (int) $request->input('question_id'),
            $request->input('answer_value')
        );

        $flowRun = $order->flowRun?->fresh();

        return response()->json([
            'current_step_key' => $flowRun?->current_step_key,
            'status' => $flowRun?->status,
        ]);
    }

    public function getProviderSlots(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $slots = $this->slotService->getAvailableSlots($order, request()->only(['date', 'timezone']));

        return response()->json(['slots' => $slots]);
    }

    public function bookSlot(BookSlotRequest $request, Order $order, string $slotId): JsonResponse
    {
        $this->authorizeOrder($order);

        try {
            $confirmation = $this->slotService->bookSlot($order, $slotId, $request->validated());
        } catch (SlotUnavailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'action' => 'refresh_slots',
            ], 409);
        }

        $flowRun = $order->flowRun?->fresh();

        return response()->json([
            'confirmation' => $confirmation,
            'current_step_key' => $flowRun?->current_step_key,
        ]);
    }

    public function submit(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $record = $this->submissionService->submit($order);
        $flowRun = $order->flowRun?->fresh();

        return response()->json([
            'consultation_record_id' => $record->id,
            'network_case_id' => $record->network_case_id,
            'current_step_key' => $flowRun?->current_step_key,
            'status' => $flowRun?->status,
        ]);
    }

    public function status(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $flowRun = $order->flowRun;

        return response()->json([
            'flow_status' => $flowRun?->status,
            'current_step_key' => $flowRun?->current_step_key,
            'pause_reason' => $flowRun?->pause_reason,
            'failure_reason' => $flowRun?->failure_reason,
        ]);
    }

    private function authorizeOrder(Order $order): void
    {
        return;
    }
}
