<?php

namespace App\Actions;

use App\Data\CreateReferralData;
use App\Enums\AuditEvent;
use App\Enums\ReferralStatus;
use App\Jobs\ProcessReferralTriageJob;
use App\Models\Referral;
use Illuminate\Database\UniqueConstraintViolationException;

final class CreateReferralAction
{
    public function __construct(private readonly LogAuditAction $logAudit) {}

    /**
     * @return array{referral: Referral, is_duplicate: bool}
     */
    public function execute(CreateReferralData $data): array
    {
        if ($data->idempotencyKey !== null) {
            $existing = Referral::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->first();

            if ($existing !== null) {
                return ['referral' => $existing, 'is_duplicate' => true];
            }
        }

        try {
            $referral = Referral::create([
                'idempotency_key' => $data->idempotencyKey,
                'patient_name' => $data->patientName,
                'patient_date_of_birth' => $data->patientDateOfBirth,
                'patient_external_id' => $data->patientExternalId,
                'referral_reason' => $data->referralReason,
                'priority' => $data->priority,
                'status' => ReferralStatus::Received,
                'referring_party' => $data->referringParty,
                'notes' => $data->notes,
            ]);
        } catch (UniqueConstraintViolationException) {
            // Race condition: another request with the same idempotency key won the insert.
            $referral = Referral::query()
                ->where('idempotency_key', $data->idempotencyKey)
                ->firstOrFail();

            return ['referral' => $referral, 'is_duplicate' => true];
        }

        $this->logAudit->execute($referral, AuditEvent::ReferralCreated, [
            'priority' => $referral->priority->value,
            'referring_party' => $referral->referring_party,
        ]);

        ProcessReferralTriageJob::dispatch($referral);

        return ['referral' => $referral, 'is_duplicate' => false];
    }
}
