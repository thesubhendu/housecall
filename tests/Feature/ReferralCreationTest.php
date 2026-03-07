<?php

use App\Enums\ReferralStatus;
use App\Jobs\ProcessReferralTriageJob;
use App\Models\Referral;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('creates a referral with valid data', function (): void {
    Queue::fake();

    $response = $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Jane Doe',
        'patient_date_of_birth' => '1990-05-15',
        'referral_reason' => 'Chest pain evaluation',
        'priority' => 'high',
        'referring_party' => 'City General Hospital',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'received')
        ->assertJsonPath('data.priority', 'high')
        ->assertJsonPath('data.patient.name', 'Jane Doe')
        ->assertJsonStructure([
            'data' => [
                'id', 'status', 'priority',
                'patient' => ['name', 'date_of_birth', 'external_id'],
                'referral_reason', 'referring_party',
                'created_at', 'updated_at',
            ],
        ]);

    $this->assertDatabaseHas('referrals', [
        'patient_name' => 'Jane Doe',
        'status' => ReferralStatus::Received->value,
        'priority' => 'high',
    ]);
});

it('dispatches a triage job after creation', function (): void {
    Queue::fake();

    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'John Smith',
        'patient_date_of_birth' => '1985-03-20',
        'referral_reason' => 'Annual check',
        'priority' => 'medium',
        'referring_party' => 'Riverside Clinic',
    ]);

    Queue::assertPushed(ProcessReferralTriageJob::class);
});

it('creates an audit log entry on creation', function (): void {
    Queue::fake();

    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Alice Cooper',
        'patient_date_of_birth' => '1975-08-10',
        'referral_reason' => 'Follow-up',
        'priority' => 'low',
        'referring_party' => 'Downtown Medical',
    ]);

    $referral = Referral::first();

    $this->assertDatabaseHas('audit_logs', [
        'referral_id' => $referral->id,
        'event' => 'referral.created',
    ]);
});

it('returns 409 and the existing referral when idempotency key is reused', function (): void {
    Queue::fake();

    $key = \Illuminate\Support\Str::uuid()->toString();

    $payload = [
        'patient_name' => 'Bob Marley',
        'patient_date_of_birth' => '1945-02-06',
        'referral_reason' => 'Cardiac evaluation',
        'priority' => 'urgent',
        'referring_party' => 'Island Clinic',
    ];

    $first = $this->withApiKey()->postJson('/api/v1/referrals', $payload, ['X-Idempotency-Key' => $key]);
    $first->assertCreated();

    $second = $this->withApiKey()->postJson('/api/v1/referrals', $payload, ['X-Idempotency-Key' => $key]);
    $second->assertStatus(409);

    $second->assertJsonPath('data.id', $first->json('data.id'));

    $this->assertDatabaseCount('referrals', 1);
});

it('allows creation without an idempotency key', function (): void {
    Queue::fake();

    $payload = [
        'patient_name' => 'No Key Patient',
        'patient_date_of_birth' => '2000-01-01',
        'referral_reason' => 'Test',
        'priority' => 'low',
        'referring_party' => 'Test Clinic',
    ];

    $this->withApiKey()->postJson('/api/v1/referrals', $payload)->assertCreated();
    $this->withApiKey()->postJson('/api/v1/referrals', $payload)->assertCreated();

    $this->assertDatabaseCount('referrals', 2);
});

it('includes optional fields when provided', function (): void {
    Queue::fake();

    $this->withApiKey()->postJson('/api/v1/referrals', [
        'patient_name' => 'Full Patient',
        'patient_date_of_birth' => '1980-12-25',
        'patient_external_id' => 'PAT-9999',
        'referral_reason' => 'Specialist consult',
        'priority' => 'medium',
        'referring_party' => 'Main Street Clinic',
        'notes' => 'Patient has a history of hypertension.',
    ])->assertCreated()
        ->assertJsonPath('data.patient.external_id', 'PAT-9999')
        ->assertJsonPath('data.notes', 'Patient has a history of hypertension.');
});
