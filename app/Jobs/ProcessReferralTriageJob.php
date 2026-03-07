<?php

namespace App\Jobs;

use App\Actions\LogAuditAction;
use App\Actions\TriageReferralAction;
use App\Enums\AuditEvent;
use App\Enums\ReferralStatus;
use App\Models\Referral;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

final class ProcessReferralTriageJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public int $timeout = 60;

    public function __construct(public readonly Referral $referral) {}

    /**
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new WithoutOverlapping($this->referral->id)];
    }

    public function handle(TriageReferralAction $triageAction, LogAuditAction $logAudit): void
    {
        if ($this->referral->status !== ReferralStatus::Received) {
            return;
        }

        $this->referral->update(['status' => ReferralStatus::Triaging]);

        $logAudit->execute($this->referral, AuditEvent::TriageStarted, [
            'attempt' => $this->attempts(),
        ]);

        $triageAction->execute($this->referral);
    }

    public function failed(?Throwable $exception): void
    {
        $logAudit = app(LogAuditAction::class);

        $logAudit->execute($this->referral, AuditEvent::StatusChanged, [
            'error' => $exception?->getMessage(),
            'failed_after_attempts' => $this->tries,
        ]);
    }
}
