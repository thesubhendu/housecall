<?php

namespace Database\Seeders;

use App\Enums\AuditEvent;
use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use App\Models\AuditLog;
use App\Models\Referral;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ComprehensiveDataSeeder extends Seeder
{
    /**
     * Seed comprehensive data for testing pagination and all possible states.
     */
    public function run(): void
    {
        $this->seedUsers();
        $referrals = $this->seedReferrals();
        $this->seedAuditLogs($referrals);
    }

    private function seedUsers(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory(15)->create();
        User::factory(5)->unverified()->create();
    }

    /**
     * @return array<int, Referral>
     */
    private function seedReferrals(): array
    {
        $referringParties = [
            'City General Hospital',
            'City Care Center',
            'Metro Health Clinic',
            'Metro Urgent Care',
            'Riverside Medical Center',
            'Sunrise Family Practice',
            'Downtown Urgent Care',
            'Valley View Hospital',
            'Northside Clinic',
            'West End Medical',
        ];

        $referrals = [];
        $baseDate = Carbon::now()->subDays(60);

        // Ensure at least 2 of each status for filter testing
        $statuses = [
            ReferralStatus::Received,
            ReferralStatus::Received,
            ReferralStatus::Triaging,
            ReferralStatus::Triaging,
            ReferralStatus::Accepted,
            ReferralStatus::Accepted,
            ReferralStatus::Rejected,
            ReferralStatus::Rejected,
            ReferralStatus::Cancelled,
            ReferralStatus::Cancelled,
        ];

        foreach ($statuses as $status) {
            $referrals[] = Referral::factory()
                ->{$status->value}()
                ->create([
                    'referring_party' => fake()->randomElement($referringParties),
                    'patient_external_id' => fake()->optional(0.7)->numerify('PAT-####'),
                    'notes' => fake()->optional(0.6)->paragraph(),
                    'created_at' => $baseDate->copy()->addDays(fake()->numberBetween(0, 55)),
                ]);
        }

        // Ensure at least 2 of each priority for filter testing
        foreach (ReferralPriority::cases() as $priority) {
            $referrals[] = Referral::factory()
                ->received()
                ->state(['priority' => $priority])
                ->create([
                    'referring_party' => fake()->randomElement($referringParties),
                    'patient_external_id' => fake()->optional(0.7)->numerify('PAT-####'),
                    'notes' => fake()->optional(0.6)->paragraph(),
                    'created_at' => $baseDate->copy()->addDays(fake()->numberBetween(0, 55)),
                ]);
        }

        // Bulk referrals for pagination (75 more = ~95 total)
        for ($i = 0; $i < 75; $i++) {
            $status = fake()->randomElement(ReferralStatus::cases());
            $referral = Referral::factory()
                ->{$status->value}()
                ->create([
                    'referring_party' => fake()->randomElement($referringParties),
                    'patient_external_id' => fake()->optional(0.5)->numerify('PAT-####'),
                    'notes' => fake()->optional(0.5)->paragraph(),
                    'created_at' => $baseDate->copy()->addDays(fake()->numberBetween(0, 55)),
                ]);
            $referrals[] = $referral;
        }

        // Edge cases: without idempotency_key, without patient_external_id, without notes
        $referrals[] = Referral::factory()
            ->received()
            ->withoutIdempotencyKey()
            ->create([
                'referring_party' => 'Edge Case Clinic',
                'patient_external_id' => null,
                'notes' => null,
                'created_at' => $baseDate->copy()->addDay(),
            ]);

        return $referrals;
    }

    /**
     * @param  array<int, Referral>  $referrals
     */
    private function seedAuditLogs(array $referrals): void
    {
        foreach ($referrals as $referral) {
            $createdAt = $referral->created_at;

            AuditLog::factory()->create([
                'referral_id' => $referral->id,
                'event' => AuditEvent::ReferralCreated,
                'metadata' => ['source' => 'seeder'],
                'created_at' => $createdAt,
            ]);

            if (in_array($referral->status, [ReferralStatus::Triaging, ReferralStatus::Accepted, ReferralStatus::Rejected], true)) {
                AuditLog::factory()->create([
                    'referral_id' => $referral->id,
                    'event' => AuditEvent::TriageStarted,
                    'metadata' => null,
                    'created_at' => $createdAt->copy()->addMinutes(5),
                ]);
            }

            if (in_array($referral->status, [ReferralStatus::Accepted, ReferralStatus::Rejected], true)) {
                AuditLog::factory()->create([
                    'referral_id' => $referral->id,
                    'event' => AuditEvent::TriageCompleted,
                    'metadata' => ['outcome' => $referral->status->value],
                    'created_at' => $createdAt->copy()->addMinutes(15),
                ]);
            }

            if ($referral->status === ReferralStatus::Cancelled) {
                AuditLog::factory()->create([
                    'referral_id' => $referral->id,
                    'event' => AuditEvent::ReferralCancelled,
                    'metadata' => ['reason' => $referral->cancelled_reason],
                    'created_at' => $referral->cancelled_at,
                ]);
            }

            // Add 0-2 extra random events for variety (exclude already-added events)
            $alreadyAdded = [
                AuditEvent::ReferralCreated,
                AuditEvent::TriageStarted,
                AuditEvent::TriageCompleted,
                AuditEvent::ReferralCancelled,
            ];
            $availableEvents = array_values(array_filter(
                AuditEvent::cases(),
                fn (AuditEvent $e) => ! in_array($e, $alreadyAdded, true)
            ));
            if (count($availableEvents) > 0) {
                $extraEvents = fake()->randomElements($availableEvents, min(fake()->numberBetween(0, 2), count($availableEvents)));
                foreach ($extraEvents as $event) {
                    AuditLog::factory()->create([
                        'referral_id' => $referral->id,
                        'event' => $event,
                        'metadata' => fake()->optional(0.5)->passthrough(['key' => fake()->word()]),
                        'created_at' => $createdAt->copy()->addMinutes(fake()->numberBetween(20, 120)),
                    ]);
                }
            }
        }
    }
}
