<?php

use App\Enums\ReferralStatus;
use App\Models\Referral;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('cancels a referral in received status', function (): void {
    $referral = Referral::factory()->received()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel", [
        'reason' => 'Patient no longer needs referral.',
    ])->assertOk()
        ->assertJsonPath('data.status', 'cancelled')
        ->assertJsonPath('data.cancelled_reason', 'Patient no longer needs referral.');

    $this->assertDatabaseHas('referrals', [
        'id' => $referral->id,
        'status' => ReferralStatus::Cancelled->value,
    ]);
});

it('cancels a referral in triaging status', function (): void {
    $referral = Referral::factory()->triaging()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

it('creates an audit log entry on cancellation', function (): void {
    $referral = Referral::factory()->received()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel", [
        'reason' => 'Duplicate submission.',
    ]);

    $this->assertDatabaseHas('audit_logs', [
        'referral_id' => $referral->id,
        'event' => 'referral.cancelled',
    ]);
});

it('cannot cancel an accepted referral', function (): void {
    $referral = Referral::factory()->accepted()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('error', 'referral_not_cancellable');
});

it('cannot cancel a rejected referral', function (): void {
    $referral = Referral::factory()->rejected()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('error', 'referral_not_cancellable');
});

it('cannot cancel an already cancelled referral', function (): void {
    $referral = Referral::factory()->cancelled()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel")
        ->assertUnprocessable()
        ->assertJsonPath('error', 'referral_not_cancellable');
});

it('sets cancelled_at timestamp on cancellation', function (): void {
    $referral = Referral::factory()->received()->create();

    $this->withApiKey()->postJson("/api/v1/referrals/{$referral->id}/cancel");

    $referral->refresh();
    expect($referral->cancelled_at)->not->toBeNull();
});
