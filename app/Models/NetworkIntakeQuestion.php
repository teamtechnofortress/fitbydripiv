<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class NetworkIntakeQuestion extends Model
{
    use HasFactory;

    public const INPUT_TEXT = 'text';

    public const INPUT_LONG_TEXT = 'long_text';

    public const INPUT_NUMBER = 'number';

    public const INPUT_SELECT = 'select';

    public const INPUT_MULTISELECT = 'multiselect';

    public const INPUT_RADIO = 'radio';

    public const INPUT_CHECKBOX = 'checkbox';

    public const INPUT_BOOLEAN = 'boolean';

    public const INPUT_DATE = 'date';

    public const INPUT_FILE = 'file';

    public const INPUT_NESTED = 'nested';

    public const INPUT_TYPES = [
        self::INPUT_TEXT,
        self::INPUT_LONG_TEXT,
        self::INPUT_NUMBER,
        self::INPUT_SELECT,
        self::INPUT_MULTISELECT,
        self::INPUT_RADIO,
        self::INPUT_CHECKBOX,
        self::INPUT_BOOLEAN,
        self::INPUT_DATE,
        self::INPUT_FILE,
        self::INPUT_NESTED,
    ];

    protected $table = 'network_intake_questions';

    protected $fillable = [
        'question_set_id',
        'question_key',
        'question_text',
        'help_text',
        'sort_order',
        'input_type',
        'options',
        'is_required',
        'validation_rules',
        'is_conditional',
        'condition_rules',
        'network_field_mapping',
        'network_validation',
        'metadata',
        'is_active',
    ];

    protected $casts = [
        'question_set_id' => 'integer',
        'sort_order' => 'integer',
        'options' => 'array',
        'is_required' => 'boolean',
        'validation_rules' => 'array',
        'is_conditional' => 'boolean',
        'condition_rules' => 'array',
        'network_validation' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function questionSet(): BelongsTo
    {
        return $this->belongsTo(NetworkIntakeQuestionSet::class, 'question_set_id');
    }

    public function setInputTypeAttribute(string $value): void
    {
        if (! in_array($value, self::INPUT_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid input_type "%s". Allowed values: %s.',
                $value,
                implode(', ', self::INPUT_TYPES)
            ));
        }

        $this->attributes['input_type'] = $value;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
