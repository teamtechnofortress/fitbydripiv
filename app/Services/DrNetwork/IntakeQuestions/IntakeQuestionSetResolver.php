<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkFlowDefinition;
use App\Models\NetworkIntakeQuestion;
use App\Models\NetworkIntakeQuestionSet;
use App\Models\Order;

class IntakeQuestionSetResolver
{
    public function __construct(
        private IntakeRuleContextBuilder $contextBuilder,
        private IntakeQuestionRuleEvaluator $ruleEvaluator,
    ) {}

    public function resolve(
        int $drNetworkId,
        string $flowKey,
        ?string $productCode = null,
        ?string $stateCode = null,
        ?Order $order = null
    ): ?array {
        $flowId = NetworkFlowDefinition::query()
            ->forNetwork($drNetworkId)
            ->forKey($flowKey)
            ->value('id');

        $set = NetworkIntakeQuestionSet::resolveFor($drNetworkId, $flowId, $productCode, $stateCode);

        return $set ? $this->hydrateSet($set, $order) : null;
    }

    private function hydrateSet(NetworkIntakeQuestionSet $set, ?Order $order): array
    {
        $questions = $set->questions()->get();
        $context = $order ? $this->contextBuilder->build($order, $set->id) : [];

        $questions = $questions
            ->reject(fn (NetworkIntakeQuestion $question): bool => $question->isHiddenFromPatient())
            ->filter(fn (NetworkIntakeQuestion $question): bool => $this->ruleEvaluator->applies($question, $context))
            ->values();

        return [
            'set_id' => $set->id,
            'set_key' => $set->set_key,
            'set_name' => $set->set_name,
            'version' => $set->version,
            'questions' => $questions->toArray(),
        ];
    }
}
