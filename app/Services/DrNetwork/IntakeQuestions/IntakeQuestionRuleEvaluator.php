<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;

class IntakeQuestionRuleEvaluator
{
    public function applies(NetworkIntakeQuestion $question, array $context): bool
    {
        if (! $question->is_conditional || empty($question->condition_rules)) {
            return true;
        }

        return $this->conditionsPass($question->condition_rules, $context);
    }

    public function conditionsPass(?array $conditions, array $context): bool
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            if (! $this->conditionPasses($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    private function conditionPasses(array $condition, array $context): bool
    {
        $source = $condition['source'] ?? $this->legacyAnswerSource($condition);

        if (! $source) {
            return false;
        }

        $actual = $context[$source] ?? null;
        $operator = $condition['operator'] ?? $this->legacyOperator($condition);
        $expected = $condition['value'] ?? $this->legacyExpectedValue($condition);

        return match ($operator) {
            'equals' => $this->equals($actual, $expected),
            'not_equals' => ! $this->equals($actual, $expected),
            'in' => in_array($this->normalizeComparable($actual), $this->normalizeList($expected), true),
            'not_in' => ! in_array($this->normalizeComparable($actual), $this->normalizeList($expected), true),
            'exists' => ! $this->isBlank($actual),
            'missing' => $this->isBlank($actual),
            'greater_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'less_than' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            default => false,
        };
    }

    private function legacyAnswerSource(array $condition): ?string
    {
        $questionKey = $condition['when'] ?? null;

        return $questionKey ? 'answers.'.$questionKey : null;
    }

    private function legacyOperator(array $condition): string
    {
        return match (true) {
            array_key_exists('equals', $condition) => 'equals',
            array_key_exists('not_equals', $condition) => 'not_equals',
            array_key_exists('in', $condition) => 'in',
            array_key_exists('not_in', $condition) => 'not_in',
            array_key_exists('exists', $condition) => 'exists',
            array_key_exists('missing', $condition) => 'missing',
            default => 'equals',
        };
    }

    private function legacyExpectedValue(array $condition): mixed
    {
        foreach (['equals', 'not_equals', 'in', 'not_in', 'exists', 'missing'] as $key) {
            if (array_key_exists($key, $condition)) {
                return $condition[$key];
            }
        }

        return null;
    }

    private function equals(mixed $actual, mixed $expected): bool
    {
        if (is_array($actual)) {
            return in_array($this->normalizeComparable($expected), $this->normalizeList($actual), true);
        }

        return $this->normalizeComparable($actual) === $this->normalizeComparable($expected);
    }

    private function normalizeList(mixed $value): array
    {
        return array_map(
            fn (mixed $item): mixed => $this->normalizeComparable($item),
            is_array($value) ? $value : [$value]
        );
    }

    private function normalizeComparable(mixed $value): mixed
    {
        if (is_bool($value) || is_numeric($value) || $value === null) {
            return $value;
        }

        return strtolower(trim((string) $value));
    }

    private function isBlank(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_array($value)) {
            return $value === [];
        }

        return trim((string) $value) === '';
    }
}
