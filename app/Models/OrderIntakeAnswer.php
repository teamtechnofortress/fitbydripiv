<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderIntakeAnswer extends Model
{
    protected $table = 'order_intake_answers';

    protected $fillable = [
        'order_id',
        'question_id',
        'answer_value',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(NetworkIntakeQuestion::class, 'question_id');
    }

    public function decodedAnswerValue(): mixed
    {
        $decoded = json_decode((string) $this->answer_value, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $this->answer_value;
    }
}
