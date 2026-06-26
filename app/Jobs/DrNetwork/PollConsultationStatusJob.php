<?php

namespace App\Jobs\DrNetwork;

use App\Models\DrNetworkFlowRun;
use App\Services\DrNetwork\ConsultationManagement\ConsultationStatusService;
use App\Services\DrNetwork\Resolvers\NetworkAdapterResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PollConsultationStatusJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public function handle(
        ConsultationStatusService $statusService,
        NetworkAdapterResolver $adapterResolver,
    ): void {
        DrNetworkFlowRun::query()
            ->where('status', DrNetworkFlowRun::STATUS_RUNNING)
            ->whereNotNull('context->network_case_id')
            ->with(['order.drNetwork'])
            ->chunkById(100, function ($flowRuns) use ($statusService, $adapterResolver): void {
                foreach ($flowRuns as $flowRun) {
                    $order = $flowRun->order;
                    $networkCaseId = $flowRun->context['network_case_id'] ?? null;

                    if (! $order?->drNetwork || ! $networkCaseId) {
                        continue;
                    }

                    try {
                        $adapter = $adapterResolver->resolve($order->drNetwork);
                        $statusPayload = $adapter->getCaseStatus($networkCaseId);
                        $networkStatus = $statusPayload['network_status'] ?? 'unknown';

                        $statusService->handleNetworkStatusUpdate($order, $networkStatus, $statusPayload['raw'] ?? []);
                    } catch (Throwable $e) {
                        Log::channel('dr_network')->error("Poll failed for flow_run {$flowRun->id}: {$e->getMessage()}");
                    }
                }
            });
    }
}
