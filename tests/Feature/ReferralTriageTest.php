<?php

use App\Actions\LogAuditAction;
use App\Actions\TriageReferralAction;
use App\Enums\AuditEvent;
use App\Enums\ReferralStatus;
use App\Jobs\ProcessReferralTriageJob;
use App\Models\Referral;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('dispatches the triage job when a referral is created', function (): void {
    Queue::fake();

    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Triage Patient',
        'patient_date_of_birth' => '1990-01-01',
        'referral_reason' => 'Evaluation needed',
        'priority' => 'high',
        'referring_party' => 'Test Clinic',
    ]);

    Queue::assertPushed(ProcessReferralTriageJob::class, function ($job): bool {
        return $job->referral->patient_name === 'Triage Patient';
    });
});

it('transitions referral to triaging then to accepted or rejected when job runs', function (): void {
    $referral = Referral::factory()->received()->urgent()->create();

    (new ProcessReferralTriageJob($referral))->handle(
        new TriageReferralAction(new LogAuditAction),
        new LogAuditAction
    );

    $referral->refresh();

    expect($referral->status)->toBeIn([ReferralStatus::Accepted, ReferralStatus::Rejected]);
    expect($referral->triage_notes)->not->toBeNull();
});

it('logs triage started and completed audit events', function (): void {
    $referral = Referral::factory()->received()->create();

    (new ProcessReferralTriageJob($referral))->handle(
        new TriageReferralAction(new LogAuditAction),
        new LogAuditAction
    );

    $this->assertDatabaseHas('audit_logs', [
        'referral_id' => $referral->id,
        'event' => AuditEvent::TriageStarted->value,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'referral_id' => $referral->id,
        'event' => AuditEvent::TriageCompleted->value,
    ]);
});

it('skips triage if referral is not in received status', function (): void {
    $referral = Referral::factory()->cancelled()->create();

    (new ProcessReferralTriageJob($referral))->handle(
        new TriageReferralAction(new LogAuditAction),
        new LogAuditAction
    );

    $referral->refresh();

    expect($referral->status)->toBe(ReferralStatus::Cancelled);
});
