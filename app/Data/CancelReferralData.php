<?php

namespace App\Data;

final readonly class CancelReferralData
{
    public function __construct(
        public string $referralId,
        public ?string $reason,
    ) {}
}
