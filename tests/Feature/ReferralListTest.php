<?php

use App\Enums\ReferralStatus;
use App\Models\Referral;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lists referrals with pagination', function (): void {
    Referral::factory()->count(20)->create();

    $this->withApiKey()->getJson('/api/v1/referrals')
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'links' => ['first', 'last', 'prev', 'next'],
            'meta' => ['current_page', 'total', 'per_page'],
        ]);
});

it('respects per_page parameter', function (): void {
    Referral::factory()->count(10)->create();

    $response = $this->withApiKey()->getJson('/api/v1/referrals?per_page=3');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);
    expect($response->json('meta.per_page'))->toBe(3);
});

it('filters by status', function (): void {
    Referral::factory()->count(3)->received()->create();
    Referral::factory()->count(2)->accepted()->create();

    $response = $this->withApiKey()->getJson('/api/v1/referrals?status=received');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(3);

    collect($response->json('data'))->each(
        fn ($item) => expect($item['status'])->toBe(ReferralStatus::Received->value)
    );
});

it('filters by priority', function (): void {
    Referral::factory()->count(2)->urgent()->create();
    Referral::factory()->count(4)->create(['priority' => 'low']);

    $response = $this->withApiKey()->getJson('/api/v1/referrals?priority=urgent');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('returns an empty list when no referrals exist', function (): void {
    $this->withApiKey()->getJson('/api/v1/referrals')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});

it('shows a single referral', function (): void {
    $referral = Referral::factory()->create();

    $this->withApiKey()->getJson("/api/v1/referrals/{$referral->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $referral->id);
});
