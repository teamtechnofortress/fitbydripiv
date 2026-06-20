<?php

namespace App\Services\DrNetwork\Resolvers;

use App\Models\NetworkProductMapping;

class ProductIdentifierResolver
{
    public function resolve(int $drNetworkId, string $productId): ?string
    {
        return NetworkProductMapping::query()
            ->forNetwork($drNetworkId)
            ->forProduct($productId)
            ->active()
            ->value('identifier');
    }
}
