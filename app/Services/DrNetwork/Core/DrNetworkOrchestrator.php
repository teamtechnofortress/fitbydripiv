<?php

namespace App\Services\DrNetwork\Core;

use App\Models\DrNetworkFlowRun;
use App\Models\Order;
use App\Services\DrNetwork\Adapters\Contracts\DoctorNetworkAdapter;
use App\Services\DrNetwork\Assignment\DrNetworkAssignmentService;
use App\Services\DrNetwork\Flow\FlowRunner;
use App\Services\DrNetwork\Resolvers\NetworkAdapterResolver;
use RuntimeException;

class DrNetworkOrchestrator
{
    public function __construct(
        private DrNetworkAssignmentService $assignmentService,
        private FlowRunner $flowRunner,
        private NetworkAdapterResolver $adapterResolver,
    ) {
    }

    public function startForOrder(Order $order): DrNetworkFlowRun
    {
        $flowRun = $this->assignmentService->assign($order);

        if ($flowRun->status === DrNetworkFlowRun::STATUS_PENDING) {
            $this->flowRunner->start($flowRun);
        }

        return $flowRun->refresh();
    }

    public function adapterFor(Order $order): DoctorNetworkAdapter
    {
        $order->loadMissing('drNetwork');

        if (! $order->drNetwork) {
            throw new RuntimeException('Order has not been assigned to a doctor network.');
        }

        return $this->adapterResolver->resolve($order->drNetwork);
    }
}
