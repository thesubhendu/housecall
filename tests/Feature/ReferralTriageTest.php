<?php

use App\Actions\TriageReferralAction;
use App\Enums\AuditEvent;
use App\Enums\ReferralPriority;
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

    app()->call([new ProcessReferralTriageJob($referral), 'handle']);

    $referral->refresh();

    expect($referral->status)->toBeIn([ReferralStatus::Accepted, ReferralStatus::Rejected]);
    expect($referral->triage_notes)->not->toBeNull();
});

it('logs triage started and completed audit events', function (): void {
    $referral = Referral::factory()->received()->create();

    app()->call([new ProcessReferralTriageJob($referral), 'handle']);

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

    app()->call([new ProcessReferralTriageJob($referral), 'handle']);

    $referral->refresh();

    expect($referral->status)->toBe(ReferralStatus::Cancelled);
});

it('does not overwrite cancelled status when user cancels during triage', function (): void {
    $referral = Referral::factory()->received()->urgent()->create();
    $referral->update(['status' => ReferralStatus::Triaging]);

    $referral->update([
        'status' => ReferralStatus::Cancelled,
        'cancelled_at' => now(),
    ]);

    app(TriageReferralAction::class)->execute($referral);

    $referral->refresh();

    expect($referral->status)->toBe(ReferralStatus::Cancelled);
});

it('keeps referral cancelled when triage job runs after user cancels via API', function (): void {
    $referral = Referral::factory()->received()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel", [
        'reason' => 'Patient withdrew.',
    ])->assertOk();

    app()->call([new ProcessReferralTriageJob($referral), 'handle']);

    $referral->refresh();

    expect($referral->status)->toBe(ReferralStatus::Cancelled)
        ->and($referral->cancelled_reason)->toBe('Patient withdrew.');
});

it('rolls back triage state and can be retried after a failure', function (): void {
    $referral = Referral::factory()->received()->urgent()->create();

    Referral::query()
        ->whereKey($referral->id)
        ->update(['priority' => 'invalid']);

    $failedAttemptReferral = Referral::query()->findOrFail($referral->id);

    expect(fn () => app()->call([new ProcessReferralTriageJob($failedAttemptReferral), 'handle']))
        ->toThrow(ValueError::class);

    $referral->refresh();

    expect($referral->status)->toBe(ReferralStatus::Received);

    $this->assertDatabaseMissing('audit_logs', [
        'referral_id' => $referral->id,
        'event' => AuditEvent::TriageStarted->value,
    ]);

    Referral::query()
        ->whereKey($referral->id)
        ->update(['priority' => ReferralPriority::Urgent]);

    $retryReferral = Referral::query()->findOrFail($referral->id);

    app()->call([new ProcessReferralTriageJob($retryReferral), 'handle']);

    $referral->refresh();

    expect($referral->status)->toBe(ReferralStatus::Accepted);

    $this->assertDatabaseHas('audit_logs', [
        'referral_id' => $referral->id,
        'event' => AuditEvent::TriageStarted->value,
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'referral_id' => $referral->id,
        'event' => AuditEvent::TriageCompleted->value,
    ]);
});
