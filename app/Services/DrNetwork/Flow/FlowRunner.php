<?php

namespace App\Services\DrNetwork\Flow;

use App\Exceptions\DrNetwork\FlowStepMismatchException;
use App\Models\DrNetworkFlowRun;
use App\Models\DrNetworkFlowRunStep;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FlowRunner
{
    public function start(DrNetworkFlowRun $flowRun): DrNetworkFlowRun
    {
        return DB::transaction(function () use ($flowRun): DrNetworkFlowRun {
            $flowRun->loadMissing('flowDefinition');

            Log::channel('dr_network')->info('FlowRunner start requested.', [
                'flow_run_id' => $flowRun->id,
                'order_id' => $flowRun->order_id,
                'dr_network_id' => $flowRun->dr_network_id,
                'flow_id' => $flowRun->flow_id,
                'flow_key' => $flowRun->flowDefinition?->flow_key,
                'status' => $flowRun->status,
                'current_step_key' => $flowRun->current_step_key,
            ]);

            $firstStep = FlowStepSequence::first($flowRun->flowDefinition->steps ?? []);

            if (! $firstStep) {
                Log::channel('dr_network')->warning('FlowRunner found no runtime steps; completing flow run.', [
                    'flow_run_id' => $flowRun->id,
                    'order_id' => $flowRun->order_id,
                    'flow_id' => $flowRun->flow_id,
                    'flow_key' => $flowRun->flowDefinition?->flow_key,
                ]);

                return $this->complete($flowRun, ['completed_reason' => 'no_steps']);
            }

            Log::channel('dr_network')->info('FlowRunner resolved first runtime step.', [
                'flow_run_id' => $flowRun->id,
                'order_id' => $flowRun->order_id,
                'first_step_key' => $firstStep['step_key'],
                'first_step_order' => $firstStep['order'] ?? null,
            ]);

            $flowRun->update([
                'status' => DrNetworkFlowRun::STATUS_RUNNING,
                'current_step_key' => $firstStep['step_key'],
                'started_at' => $flowRun->started_at ?? now(),
            ]);

            Log::channel('dr_network')->info('FlowRunner marked flow run as running.', [
                'flow_run_id' => $flowRun->id,
                'order_id' => $flowRun->order_id,
                'current_step_key' => $firstStep['step_key'],
            ]);

            $this->openStep($flowRun, $firstStep['step_key']);

            return $flowRun->refresh();
        });
    }

    public function advance(DrNetworkFlowRun $flowRun, string $completedStepKey, array $output = []): DrNetworkFlowRun
    {
        if ($flowRun->current_step_key !== $completedStepKey) {
            throw new FlowStepMismatchException(sprintf(
                'Cannot advance step [%s]: flow run is currently on [%s].',
                $completedStepKey,
                $flowRun->current_step_key
            ));
        }

        return DB::transaction(function () use ($flowRun, $completedStepKey, $output): DrNetworkFlowRun {
            $flowRun->loadMissing('flowDefinition');

            $this->closeStep(
                $flowRun,
                $completedStepKey,
                DrNetworkFlowRunStep::STATUS_COMPLETED,
                $output
            );

            $nextStep = FlowStepSequence::next($flowRun->flowDefinition->steps ?? [], $completedStepKey);

            if (! $nextStep) {
                return $this->complete($flowRun, $output);
            }

            $flowRun->update(['current_step_key' => $nextStep['step_key']]);
            $this->openStep($flowRun, $nextStep['step_key']);

            return $flowRun->refresh();
        });
    }

    public function pause(DrNetworkFlowRun $flowRun, string $reason): DrNetworkFlowRun
    {
        $flowRun->update([
            'status' => DrNetworkFlowRun::STATUS_PAUSED,
            'pause_reason' => $reason,
            'paused_at' => now(),
        ]);

        return $flowRun->refresh();
    }

    public function resume(DrNetworkFlowRun $flowRun): DrNetworkFlowRun
    {
        $flowRun->update([
            'status' => DrNetworkFlowRun::STATUS_RUNNING,
            'pause_reason' => null,
            'paused_at' => null,
        ]);

        return $flowRun->refresh();
    }

    public function fail(DrNetworkFlowRun $flowRun, string $reason, array $context = []): DrNetworkFlowRun
    {
        return DB::transaction(function () use ($flowRun, $reason, $context): DrNetworkFlowRun {
            $errorMessage = $this->failureDisplayMessage($reason, $context);
            $failureContext = array_merge($context, [
                'failure_reason' => $reason,
                'failure_message' => $errorMessage,
            ]);

            $this->closeStep(
                $flowRun,
                $flowRun->current_step_key,
                DrNetworkFlowRunStep::STATUS_FAILED,
                $failureContext,
                $errorMessage
            );

            $flowRun->update([
                'status' => DrNetworkFlowRun::STATUS_FAILED,
                'failure_reason' => $reason,
                'failed_at' => now(),
                'context' => array_merge($flowRun->context ?? [], $failureContext),
            ]);

            return $flowRun->refresh();
        });
    }

    public function complete(DrNetworkFlowRun $flowRun, array $context = []): DrNetworkFlowRun
    {
        return DB::transaction(function () use ($flowRun, $context): DrNetworkFlowRun {
            $this->closeStep(
                $flowRun,
                $flowRun->current_step_key,
                DrNetworkFlowRunStep::STATUS_COMPLETED,
                $context
            );

            $flowRun->update([
                'status' => DrNetworkFlowRun::STATUS_COMPLETED,
                'completed_at' => now(),
                'context' => array_merge($flowRun->context ?? [], $context),
            ]);

            return $flowRun->refresh();
        });
    }

    private function openStep(DrNetworkFlowRun $flowRun, string $stepKey): void
    {
        $step = DrNetworkFlowRunStep::query()->create([
            'flow_run_id' => $flowRun->id,
            'step_key' => $stepKey,
            'status' => DrNetworkFlowRunStep::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        Log::channel('dr_network')->info('FlowRunner opened flow run step.', [
            'flow_run_id' => $flowRun->id,
            'flow_run_step_id' => $step->id,
            'order_id' => $flowRun->order_id,
            'step_key' => $stepKey,
            'status' => $step->status,
        ]);
    }

    private function closeStep(
        DrNetworkFlowRun $flowRun,
        ?string $stepKey,
        string $status,
        array $output = [],
        ?string $errorMessage = null
    ): void {
        if (! $stepKey) {
            return;
        }

        DrNetworkFlowRunStep::query()
            ->where('flow_run_id', $flowRun->id)
            ->where('step_key', $stepKey)
            ->where('status', DrNetworkFlowRunStep::STATUS_IN_PROGRESS)
            ->latest('id')
            ->first()
            ?->update([
                'status' => $status,
                'output' => $output,
                'error_message' => $errorMessage,
                'completed_at' => now(),
            ]);
    }

    private function failureDisplayMessage(string $reason, array $context): string
    {
        $message = $context['failure_message'] ?? $context['error_message'] ?? null;

        if (is_string($message) && trim($message) !== '') {
            return trim($message);
        }

        return str($reason)->replace('_', ' ')->ucfirst()->toString();
    }
}
