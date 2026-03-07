<?php

use App\Models\Referral;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('returns 401 for unauthenticated list request', function (): void {
    $this->getJson('/api/v1/referrals')->assertUnauthorized();
});

it('returns 401 for unauthenticated create request', function (): void {
    $this->postJson('/api/v1/referrals', [])->assertUnauthorized();
});

it('returns 401 for unauthenticated show request', function (): void {
    $referral = Referral::factory()->create();

    $this->getJson("/api/v1/referrals/{$referral->id}")->assertUnauthorized();
});

it('returns 401 for unauthenticated cancel request', function (): void {
    $referral = Referral::factory()->create();

    $this->postJson("/api/v1/referrals/{$referral->id}/cancel")->assertUnauthorized();
});

it('returns 401 when a wrong token is provided', function (): void {
    $this->withHeader('Authorization', 'Bearer wrong-token')
        ->getJson('/api/v1/referrals')
        ->assertUnauthorized();
});

it('allows access with the correct api key', function (): void {
    $this->withApiKey()->getJson('/api/v1/referrals')->assertOk();
});

it('returns 404 with structured JSON for an unknown referral', function (): void {
    $this->withApiKey()
        ->getJson('/api/v1/referrals/nonexistent-id-00000000000')
        ->assertNotFound()
        ->assertJsonPath('error', 'not_found');
});
