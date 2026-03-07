<?php

namespace App\Actions;

use App\Enums\AuditEvent;
use App\Models\AuditLog;
use App\Models\Referral;

final class LogAuditAction
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function execute(Referral $referral, AuditEvent $event, ?array $metadata = null): AuditLog
    {
        return AuditLog::create([
            'referral_id' => $referral->id,
            'event' => $event,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }
}
