<?php

namespace App\Services\DrNetwork\Resolvers;

use App\Models\DrNetwork;
use App\Services\DrNetwork\Adapters\Contracts\DoctorNetworkAdapter;
use RuntimeException;

class NetworkAdapterResolver
{
    public function resolve(DrNetwork $network): DoctorNetworkAdapter
    {
        $adapterClass = config("dr_networks.adapters.{$network->adapter_key}");

        if (! $adapterClass || ! class_exists($adapterClass)) {
            throw new RuntimeException("No adapter registered for adapter_key [{$network->adapter_key}].");
        }

        $adapter = app($adapterClass);

        if (! $adapter instanceof DoctorNetworkAdapter) {
            throw new RuntimeException(sprintf(
                'Adapter [%s] must implement %s.',
                $adapterClass,
                DoctorNetworkAdapter::class
            ));
        }

        return $adapter;
    }
}
