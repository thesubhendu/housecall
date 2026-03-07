<?php

namespace App\Actions;

use App\Data\CancelReferralData;
use App\Enums\AuditEvent;
use App\Enums\ReferralStatus;
use App\Exceptions\ReferralCannotBeCancelledException;
use App\Models\Referral;

final class CancelReferralAction
{
    public function __construct(private readonly LogAuditAction $logAudit) {}

    public function execute(Referral $referral, CancelReferralData $data): Referral
    {
        if (! $referral->status->isCancellable()) {
            throw new ReferralCannotBeCancelledException(
                "Referral cannot be cancelled from status [{$referral->status->value}]."
            );
        }

        $previousStatus = $referral->status;

        $referral->update([
            'status' => ReferralStatus::Cancelled,
            'cancelled_reason' => $data->reason,
            'cancelled_at' => now(),
        ]);

        $this->logAudit->execute($referral, AuditEvent::ReferralCancelled, [
            'previous_status' => $previousStatus->value,
            'reason' => $data->reason,
        ]);

        return $referral;
    }
}
