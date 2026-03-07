<?php

namespace App\Data;

use App\Enums\ReferralPriority;
use App\Http\Requests\CreateReferralRequest;

final readonly class CreateReferralData
{
    public function __construct(
        public string $patientName,
        public string $patientDateOfBirth,
        public ?string $patientExternalId,
        public string $referralReason,
        public ReferralPriority $priority,
        public string $referringParty,
        public ?string $notes,
        public ?string $idempotencyKey,
    ) {}

    public static function fromRequest(CreateReferralRequest $request): self
    {
        return new self(
            patientName: $request->string('patient_name')->toString(),
            patientDateOfBirth: $request->string('patient_date_of_birth')->toString(),
            patientExternalId: $request->string('patient_external_id')->toString() ?: null,
            referralReason: $request->string('referral_reason')->toString(),
            priority: ReferralPriority::from($request->string('priority')->toString()),
            referringParty: $request->string('referring_party')->toString(),
            notes: $request->string('notes')->toString() ?: null,
            idempotencyKey: $request->header('X-Idempotency-Key'),
        );
    }
}
