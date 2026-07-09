<?php

namespace App\Services\DrNetwork\IntakeQuestions;

use App\Models\NetworkIntakeQuestion;

class IntakeQuestionRuleEvaluator
{
    public const OPERATOR_EQUALS = 'equals';

    public const OPERATOR_NOT_EQUALS = 'not_equals';

    public const OPERATOR_IN = 'in';

    public const OPERATOR_NOT_IN = 'not_in';

    public const OPERATOR_EXISTS = 'exists';

    public const OPERATOR_MISSING = 'missing';

    public const OPERATOR_GREATER_THAN = 'greater_than';

    public const OPERATOR_LESS_THAN = 'less_than';

    public const OPERATORS = [
        self::OPERATOR_EQUALS,
        self::OPERATOR_NOT_EQUALS,
        self::OPERATOR_IN,
        self::OPERATOR_NOT_IN,
        self::OPERATOR_EXISTS,
        self::OPERATOR_MISSING,
        self::OPERATOR_GREATER_THAN,
        self::OPERATOR_LESS_THAN,
    ];

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
            self::OPERATOR_EQUALS => $this->equals($actual, $expected),
            self::OPERATOR_NOT_EQUALS => ! $this->equals($actual, $expected),
            self::OPERATOR_IN => in_array($this->normalizeComparable($actual), $this->normalizeList($expected), true),
            self::OPERATOR_NOT_IN => ! in_array($this->normalizeComparable($actual), $this->normalizeList($expected), true),
            self::OPERATOR_EXISTS => ! $this->isBlank($actual),
            self::OPERATOR_MISSING => $this->isBlank($actual),
            self::OPERATOR_GREATER_THAN => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            self::OPERATOR_LESS_THAN => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
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
            array_key_exists(self::OPERATOR_EQUALS, $condition) => self::OPERATOR_EQUALS,
            array_key_exists(self::OPERATOR_NOT_EQUALS, $condition) => self::OPERATOR_NOT_EQUALS,
            array_key_exists(self::OPERATOR_IN, $condition) => self::OPERATOR_IN,
            array_key_exists(self::OPERATOR_NOT_IN, $condition) => self::OPERATOR_NOT_IN,
            array_key_exists(self::OPERATOR_EXISTS, $condition) => self::OPERATOR_EXISTS,
            array_key_exists(self::OPERATOR_MISSING, $condition) => self::OPERATOR_MISSING,
            default => self::OPERATOR_EQUALS,
        };
    }

    private function legacyExpectedValue(array $condition): mixed
    {
        foreach ([
            self::OPERATOR_EQUALS,
            self::OPERATOR_NOT_EQUALS,
            self::OPERATOR_IN,
            self::OPERATOR_NOT_IN,
            self::OPERATOR_EXISTS,
            self::OPERATOR_MISSING,
        ] as $key) {
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
