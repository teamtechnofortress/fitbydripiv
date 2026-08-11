<?php

namespace App\Services\DrNetwork\ConsultationManagement;

use App\Models\ConsultationRecord;
use App\Models\Order;
use App\Models\OrderConsent;
use App\Services\DrNetwork\Adapters\OlaHealth\OlaHealthMapper;
use App\Services\DrNetwork\Core\DrNetworkOrchestrator;
use App\Services\DrNetwork\Finance\DrNetworkFinanceService;
use App\Services\DrNetwork\Flow\FlowRunner;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConsultationSubmissionService
{
    public function __construct(
        private DrNetworkOrchestrator $orchestrator,
        private FlowRunner $flowRunner,
        private OlaHealthMapper $mapper,
        private DrNetworkFinanceService $financeService,
    ) {}

    public function submit(Order $order): ConsultationRecord
    {
        $order->loadMissing('flowRun');

        if (! $order->flowRun) {
            throw new RuntimeException('Order does not have a doctor network flow run.');
        }

        $adapter = $this->orchestrator->adapterFor($order);
        $flowRun = $order->flowRun;
        $context = $flowRun->context ?? [];
        $payload = $this->mapper->buildSubmissionPayload($order, $context);
        $response = $adapter->submitCase($payload);

        return DB::transaction(function () use ($order, $flowRun, $context, $response, $adapter): ConsultationRecord {
            $record = ConsultationRecord::query()->updateOrCreate(
                ['order_id' => $order->id],
                [
                    'dr_network_id' => $order->dr_network_id,
                    'network_case_id' => $response['case_id'],
                    'network_status' => $response['status'],
                    'internal_status' => $adapter->translateStatus((string) $response['status']),
                    'submitted_at' => now(),
                    'network_metadata' => $response['raw'] ?? [],
                ]
            );

            $this->financeService->recordTransactionForSubmission($order, $record);

            OrderConsent::query()
                ->where('order_id', $order->id)
                ->whereNull('consultation_record_id')
                ->update(['consultation_record_id' => $record->id]);

            $flowRun->update([
                'context' => array_merge($context, [
                    'network_case_id' => $response['case_id'],
                ]),
            ]);

            if ($flowRun->current_step_key === 'review_and_submit') {
                $this->flowRunner->advance($flowRun->refresh(), 'review_and_submit', [
                    'network_case_id' => $response['case_id'],
                ]);
            }

            return $record;
        });
    }
}
