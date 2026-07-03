<?php

namespace App\Services\DrNetwork\Resolvers;

use App\Models\NetworkProductMapping;

class ProductIdentifierResolver
{
    public function resolve(int $drNetworkId, string $productId, int $flowId): ?NetworkProductMapping
    {
        return NetworkProductMapping::query()
            ->forNetwork($drNetworkId)
            ->forProduct($productId)
            ->forFlow($flowId)
            ->active()
            ->first();
    }
}
