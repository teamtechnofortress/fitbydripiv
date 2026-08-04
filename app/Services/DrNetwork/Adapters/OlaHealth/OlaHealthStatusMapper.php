<?php

namespace App\Services\DrNetwork\Adapters\OlaHealth;

class OlaHealthStatusMapper
{
    public const INTERNAL_PRESCRIPTION_APPROVED = 'prescription_approved';

    public const INTERNAL_CONSULTATION_REJECTED = 'consultation_rejected';

    public const INTERNAL_PENDING_PATIENT_INFO = 'pending_patient_info';

    public const INTERNAL_IN_REVIEW = 'in_review';

    public static function toInternal(string $olaStatus): string
    {
        return match (strtolower(trim($olaStatus))) {
            'approved', 'prescription_issued', 'completed', 'closed' => self::INTERNAL_PRESCRIPTION_APPROVED,
            'accept', 'accepted' => self::INTERNAL_IN_REVIEW,
            'reject', 'rejected', 'declined', 'not_eligible', 'cancelled' => self::INTERNAL_CONSULTATION_REJECTED,
            'pending_info', 'info_needed', 'on_hold', 'needs_review' => self::INTERNAL_PENDING_PATIENT_INFO,
            default => self::INTERNAL_IN_REVIEW,
        };
    }
}
