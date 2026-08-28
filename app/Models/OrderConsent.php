<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderConsent extends Model
{
    public const KEY_TELEHEALTH_TERMS_CONSENT = 'telehealth_terms_consent';

    public const TITLE_FITBYSHOT_TERMS_CONSENT = 'FitByShot Terms Consent';

    public const VERSION_FITBYSHOT_TERMS_CONSENT = '2026-08-11';

    protected $fillable = [
        'order_id',
        'patient_id',
        'consultation_record_id',
        'consent_key',
        'consent_title',
        'content_version',
        'content_hash',
        'accepted',
        'accepted_at',
        'rejected_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'accepted_at' => 'datetime',
        'rejected_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultationRecord(): BelongsTo
    {
        return $this->belongsTo(ConsultationRecord::class);
    }
}
