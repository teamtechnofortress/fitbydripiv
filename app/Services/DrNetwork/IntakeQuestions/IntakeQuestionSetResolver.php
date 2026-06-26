<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkFlowDefinition;
use App\Models\NetworkIntakeQuestionSet;

class IntakeQuestionSetResolver
{
    public function resolve(int $drNetworkId, string $flowKey, ?string $productCode = null, ?string $stateCode = null): ?array
    {
        $flowId = NetworkFlowDefinition::query()
            ->where('flow_key', $flowKey)
            ->value('id');

        $set = NetworkIntakeQuestionSet::resolveFor($drNetworkId, $flowId, $productCode, $stateCode);

        return $set ? $this->hydrateSet($set) : null;
    }

    private function hydrateSet(NetworkIntakeQuestionSet $set): array
    {
        return [
            'set_id' => $set->id,
            'set_key' => $set->set_key,
            'set_name' => $set->set_name,
            'version' => $set->version,
            'questions' => $set->questions()->get()->toArray(),
        ];
    }
}
