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
        $isAccepted = $referral->priority->acceptanceProbability() >= 0.80;
        $outcome = $isAccepted ? ReferralStatus::Accepted : ReferralStatus::Rejected;

        $triageNotes = $isAccepted
            ? "Referral accepted based on [{$referral->priority->value}] priority evaluation."
            : "Referral rejected based on [{$referral->priority->value}] priority evaluation.";

        $updated = Referral::query()
            ->where('id', $referral->id)
            ->where('status', ReferralStatus::Triaging)
            ->update([
                'status' => $outcome,
                'triage_notes' => $triageNotes,
            ]);

        if ($updated === 0) {
            return $referral;
        }

        $referral->status = $outcome;
        $referral->triage_notes = $triageNotes;

        $this->logAudit->execute($referral, AuditEvent::TriageCompleted, [
            'outcome' => $outcome->value,
            'priority' => $referral->priority->value,
        ]);

        return $referral;
    }
}
