<?php

namespace App\Services\DrNetwork\DocumentManagement;

use App\Models\NetworkDocumentRule;
use App\Models\OrderDocument;

class DocumentRequirementValidator
{
    public function validate(int $orderId, int $drNetworkId, string $flowKey, ?string $stateCode, ?string $productCode): array
    {
        $rules = NetworkDocumentRule::query()
            ->forNetwork($drNetworkId)
            ->forFlow($flowKey)
            ->forState($stateCode)
            ->forProduct($productCode)
            ->active()
            ->ordered()
            ->get();

        $uploadedDocIds = OrderDocument::query()
            ->where('order_id', $orderId)
            ->where('status', OrderDocument::STATUS_VERIFIED)
            ->pluck('document_type_id')
            ->all();

        $satisfied = [];
        $unsatisfied = [];

        foreach ($rules as $rule) {
            if ($this->ruleIsSatisfied($rule, $uploadedDocIds)) {
                $satisfied[] = $rule->rule_key;
            } elseif ($rule->is_required) {
                $unsatisfied[] = $rule->rule_key;
            }
        }

        return [
            'all_satisfied' => $unsatisfied === [],
            'satisfied' => $satisfied,
            'unsatisfied' => $unsatisfied,
        ];
    }

    private function ruleIsSatisfied(NetworkDocumentRule $rule, array $uploadedDocIds): bool
    {
        $required = $rule->document_ids ?? [];

        return match ($rule->operator) {
            NetworkDocumentRule::OPERATOR_ANY => array_intersect($required, $uploadedDocIds) !== [],
            NetworkDocumentRule::OPERATOR_ALL => array_diff($required, $uploadedDocIds) === [],
            NetworkDocumentRule::OPERATOR_EXACT => array_diff($required, $uploadedDocIds) === []
                && array_diff($uploadedDocIds, $required) === [],
            default => false,
        };
    }
}
