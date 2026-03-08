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

it('filters by referring_party prefix', function (): void {
    Referral::factory()->create(['referring_party' => 'Dr. Smith']);
    Referral::factory()->create(['referring_party' => 'Dr. Smithson']);
    Referral::factory()->create(['referring_party' => 'Dr. Jones']);

    $response = $this->withApiKey()->getJson('/api/v1/referrals?referring_party=Dr.%20Smith');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('filters by patient_external_id', function (): void {
    Referral::factory()->create(['patient_external_id' => 'EXT-123']);
    Referral::factory()->create(['patient_external_id' => 'EXT-456']);

    $response = $this->withApiKey()->getJson('/api/v1/referrals?patient_external_id=EXT-123');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.patient.external_id'))->toBe('EXT-123');
});

it('filters by date range', function (): void {
    Referral::factory()->create(['created_at' => now()->subDays(5)]);
    Referral::factory()->create(['created_at' => now()->subDays(2)]);
    Referral::factory()->create(['created_at' => now()->subDays(1)]);

    $response = $this->withApiKey()->getJson('/api/v1/referrals?created_from='.now()->subDays(3)->format('Y-m-d').'&created_to='.now()->format('Y-m-d'));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

it('respects sort and order parameters', function (): void {
    Referral::factory()->create(['created_at' => now()->subDays(2)]);
    Referral::factory()->create(['created_at' => now()->subDays(1)]);

    $response = $this->withApiKey()->getJson('/api/v1/referrals?sort=created_at&order=asc');

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('data.0.id'))->toBe(Referral::orderBy('created_at')->first()->id);
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
