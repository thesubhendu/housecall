<?php

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('fails when required fields are missing', function (string $field): void {
    $payload = [
        'patient_name' => 'Jane Doe',
        'patient_date_of_birth' => '1990-05-15',
        'referral_reason' => 'Chest pain',
        'priority' => 'medium',
        'referring_party' => 'City Clinic',
    ];

    unset($payload[$field]);

    $this->withApiKey()->postJson('/api/v1/referrals', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([$field]);
})->with([
    'patient_name',
    'patient_date_of_birth',
    'referral_reason',
    'priority',
    'referring_party',
]);

it('fails with an invalid priority value', function (): void {
    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Jane Doe',
        'patient_date_of_birth' => '1990-05-15',
        'referral_reason' => 'Chest pain',
        'priority' => 'critical',
        'referring_party' => 'City Clinic',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['priority']);
});

it('fails when date of birth is in the future', function (): void {
    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Future Patient',
        'patient_date_of_birth' => now()->addYear()->toDateString(),
        'referral_reason' => 'Test',
        'priority' => 'low',
        'referring_party' => 'Test Clinic',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['patient_date_of_birth']);
});

it('fails when date of birth is today', function (): void {
    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Today Patient',
        'patient_date_of_birth' => now()->toDateString(),
        'referral_reason' => 'Test',
        'priority' => 'low',
        'referring_party' => 'Test Clinic',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['patient_date_of_birth']);
});

it('returns a structured JSON error response on validation failure', function (): void {
    $this->withApiKey()->postJson('/api/v1/referrals', [])
        ->assertUnprocessable()
        ->assertJsonStructure([
            'message',
            'errors' => [],
        ]);
});
