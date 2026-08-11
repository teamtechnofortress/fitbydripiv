<?php

namespace App\Http\Controllers\DrNetwork;

use App\Exceptions\DrNetwork\NetworkAssignmentException;
use App\Exceptions\DrNetwork\SlotUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DrNetwork\BookSlotRequest;
use App\Http\Requests\DrNetwork\ReviewIntakeAnswersRequest;
use App\Http\Requests\DrNetwork\SaveIntakeAnswerRequest;
use App\Http\Requests\DrNetwork\UploadDocumentRequest;
use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use App\Services\DrNetwork\ConsultationManagement\ConsultationSubmissionService;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use App\Services\DrNetwork\DocumentManagement\DocumentRequirementResolver;
use App\Services\DrNetwork\DocumentManagement\DocumentUploadService;
use App\Services\DrNetwork\IntakeQuestions\IntakeAnswerReviewService;
use App\Services\DrNetwork\IntakeQuestions\IntakeAnswerService;
use App\Services\DrNetwork\IntakeQuestions\IntakeQuestionSetResolver;
use App\Services\DrNetwork\IntakeQuestions\PreviousIntakeAnswerService;
use App\Services\DrNetwork\ProviderScheduling\ProviderSlotService;
use App\Services\Consent\ConsentBlockingRuleEvaluator;
use App\Services\Consent\OrderConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class DrNetworkFlowController extends Controller
{
    public function __construct(
        private DrNetworkOrchestrator $orchestrator,
        private DocumentRequirementResolver $documentRequirementResolver,
        private DocumentUploadService $documentUploadService,
        private IntakeQuestionSetResolver $questionSetResolver,
        private PreviousIntakeAnswerService $previousIntakeAnswerService,
        private IntakeAnswerReviewService $answerReviewService,
        private IntakeAnswerService $answerService,
        private ProviderSlotService $slotService,
        private ConsultationSubmissionService $submissionService,
        private OrderConsentService $consentService,
        private ConsentBlockingRuleEvaluator $consentBlockingRuleEvaluator,
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
        $order->loadMissing(['flowRun', 'patient', 'product']);

        $flowRun = $order->flowRun;

        if (! $flowRun) {
            return response()->json(['message' => 'Flow not started.'], 404);
        }

        if ($flowRun->status === DrNetworkFlowRun::STATUS_FAILED) {
            return response()->json([
                'step' => 'failed',
                'status' => DrNetworkFlowRun::STATUS_FAILED,
                'failed_step_key' => $flowRun->current_step_key,
                'failure_reason' => $flowRun->failure_reason,
                'failure_message' => $this->failureMessage($flowRun),
                'status_message' => $this->failureMessage($flowRun),
            ]);
        }

        $payload = [
            'step' => $flowRun->current_step_key,
            'status' => $flowRun->status,
            'provider_review_requirements' => $this->providerReviewRequirements($flowRun),
            'has_provider_review_requirements' => $this->hasProviderReviewRequirements($flowRun),
        ];

        if ($flowRun->current_step_key === 'document_upload') {
            $payload['document_requirements'] = $this->documentRequirementResolver->resolve(
                $order->dr_network_id,
                $order->network_flow_key,
                $order->state_code,
                $order->product?->slug,
                $order->id
            );
        }

        if ($flowRun->current_step_key === 'intake_questions') {
            $questionSet = $this->questionSetResolver->resolve(
                $order->dr_network_id,
                $order->network_flow_key,
                $order->product?->slug,
                $order->state_code,
                $order
            );

            $payload['question_set'] = $questionSet;
            $payload['previous_same_product_intake'] = $this->previousIntakeAnswerService->forOrder(
                $order,
                $questionSet ? array_column($questionSet['questions'], 'question_key') : []
            );

            if ($questionSet) {
                $flowRun->update([
                    'context' => array_merge($flowRun->context ?? [], [
                        'question_set_id' => $questionSet['set_id'],
                    ]),
                ]);
            }

            Log::channel('dr_network')->info('Dr Network intake current-step payload prepared.', [
                'order_id' => $order->id,
                'order_uuid' => $order->order_uuid,
                'flow_run_id' => $flowRun->id,
                'current_step_key' => $flowRun->current_step_key,
                'payload' => $payload,
            ]);
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
            'can_continue' => $result['all_satisfied'],
            'satisfied' => $result['satisfied'],
            'unsatisfied' => $result['unsatisfied'],
            'current_step_key' => $flowRun?->current_step_key,
        ]);
    }

    public function completeDocumentUpload(Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        $result = $this->documentUploadService->completeDocumentUpload($order);

        if (! $result['all_satisfied']) {
            return response()->json([
                'message' => 'Required documents are not complete.',
                'all_satisfied' => false,
                'can_continue' => false,
                'satisfied' => $result['satisfied'],
                'unsatisfied' => $result['unsatisfied'],
                'current_step_key' => $order->flowRun?->current_step_key,
            ], 422);
        }

        $flowRun = $order->flowRun?->fresh();

        return response()->json([
            'message' => 'Document requirements completed.',
            'all_satisfied' => true,
            'can_continue' => true,
            'satisfied' => $result['satisfied'],
            'unsatisfied' => $result['unsatisfied'],
            'current_step_key' => $flowRun?->current_step_key,
            'status' => $flowRun?->status,
        ]);
    }

    public function reviewIntakeAnswers(ReviewIntakeAnswersRequest $request, Order $order): JsonResponse
    {
        $this->authorizeOrder($order);

        return response()->json($this->answerReviewService->review(
            $order,
            $request->validated('answers')
        ));
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
            'provider_review_requirements' => $flowRun ? $this->providerReviewRequirements($flowRun) : [],
            'has_provider_review_requirements' => $flowRun ? $this->hasProviderReviewRequirements($flowRun) : false,
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

        if ($response = $this->requiredConsentResponse($order)) {
            return $response;
        }

        $record = $this->submissionService->submit($order);
        $flowRun = $order->flowRun?->fresh();

        return response()->json([
            'consultation_record_id' => $record->id,
            'network_case_id' => $record->network_case_id,
            'current_step_key' => $flowRun?->current_step_key,
            'status' => $flowRun?->status,
            'provider_review_requirements' => $flowRun ? $this->providerReviewRequirements($flowRun) : [],
            'has_provider_review_requirements' => $flowRun ? $this->hasProviderReviewRequirements($flowRun) : false,
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
            'failure_message' => $flowRun ? $this->failureMessage($flowRun) : null,
            'status_message' => $flowRun ? ($this->failureMessage($flowRun) ?: $flowRun->pause_reason) : null,
            'provider_review_requirements' => $flowRun ? $this->providerReviewRequirements($flowRun) : [],
            'has_provider_review_requirements' => $flowRun ? $this->hasProviderReviewRequirements($flowRun) : false,
        ]);
    }

    private function requiredConsentResponse(Order $order): ?JsonResponse
    {
        $legalConsentRejection = $this->consentService->legalConsentRejection($order);

        if ($legalConsentRejection) {
            $rule = $this->consentBlockingRuleEvaluator->legalConsentRejectionRule();

            return response()->json([
                'message' => $rule['message'],
                'code' => $rule['reason'],
                'rule_key' => $rule['rule_key'],
                'reason' => $rule['reason'],
                'hard_stop_type' => $rule['hard_stop_type'],
                'conditions' => $rule['conditions'],
                'rejected_consent_key' => $legalConsentRejection->consent_key,
                'rejected_at' => $legalConsentRejection->rejected_at,
            ], 422);
        }

        $missingConsentKeys = $this->consentService->missingRequiredConsentKeys($order);

        if ($missingConsentKeys === []) {
            return null;
        }

        return response()->json([
            'message' => 'Required legal consent must be accepted before continuing.',
            'code' => 'required_consent_missing',
            'missing_required_consent_keys' => $missingConsentKeys,
        ], 422);
    }

    private function providerReviewRequirements(DrNetworkFlowRun $flowRun): array
    {
        return is_array($flowRun->context['provider_review_requirements'] ?? null)
            ? $flowRun->context['provider_review_requirements']
            : [];
    }

    private function failureMessage(DrNetworkFlowRun $flowRun): ?string
    {
        $message = $flowRun->context['failure_message'] ?? $flowRun->context['error_message'] ?? null;

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        $reason = $flowRun->failure_reason;

        if (! is_string($reason) || trim($reason) === '') {
            return null;
        }

        return str($reason)->replace('_', ' ')->ucfirst()->toString();
    }

    private function hasProviderReviewRequirements(DrNetworkFlowRun $flowRun): bool
    {
        return $this->providerReviewRequirements($flowRun) !== [];
    }

    private function authorizeOrder(Order $order): void {}
}
