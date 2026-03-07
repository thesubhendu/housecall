<?php

namespace App\Enums;

enum ReferralPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Urgent = 'urgent';

    /**
     * Likelihood of acceptance during simulated triage (0.0 – 1.0).
     */
    public function acceptanceProbability(): float
    {
        return match ($this) {
            self::Low => 0.50,
            self::Medium => 0.65,
            self::High => 0.80,
            self::Urgent => 0.95,
        };
    }
}
