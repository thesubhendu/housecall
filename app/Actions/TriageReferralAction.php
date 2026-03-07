<?php

namespace App\Actions;

use App\Enums\AuditEvent;
use App\Enums\ReferralStatus;
use App\Models\Referral;

final class TriageReferralAction
{
    public function __construct(private readonly LogAuditAction $logAudit) {}

    public function execute(Referral $referral): Referral
    {
        $acceptanceProbability = $referral->priority->acceptanceProbability();
        $isAccepted = (mt_rand(1, 100) / 100) <= $acceptanceProbability;

        $outcome = $isAccepted ? ReferralStatus::Accepted : ReferralStatus::Rejected;

        $triageNotes = $isAccepted
            ? "Referral accepted based on [{$referral->priority->value}] priority evaluation."
            : "Referral rejected based on [{$referral->priority->value}] priority evaluation.";

        $referral->update([
            'status' => $outcome,
            'triage_notes' => $triageNotes,
        ]);

        $this->logAudit->execute($referral, AuditEvent::TriageCompleted, [
            'outcome' => $outcome->value,
            'priority' => $referral->priority->value,
            'acceptance_probability' => $acceptanceProbability,
        ]);

        return $referral;
    }
}
