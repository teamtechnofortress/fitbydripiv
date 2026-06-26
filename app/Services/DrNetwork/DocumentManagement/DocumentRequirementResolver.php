<?php

namespace App\Services\DrNetwork\DocumentManagement;

use App\Models\NetworkDocumentRule;

class DocumentRequirementResolver
{
    public function resolve(int $drNetworkId, string $flowKey, ?string $stateCode = null, ?string $productCode = null): array
    {
        return NetworkDocumentRule::query()
            ->forNetwork($drNetworkId)
            ->forFlow($flowKey)
            ->forState($stateCode)
            ->forProduct($productCode)
            ->active()
            ->ordered()
            ->get()
            ->toArray();
    }
}
