<?php

namespace Database\Factories;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Referral>
 */
class ReferralFactory extends Factory
{
    public function definition(): array
    {
        return [
            'idempotency_key' => Str::uuid()->toString(),
            'patient_name' => fake()->name(),
            'patient_date_of_birth' => fake()->date('Y-m-d', '-18 years'),
            'patient_external_id' => fake()->optional()->numerify('PAT-####'),
            'referral_reason' => fake()->sentence(),
            'priority' => fake()->randomElement(ReferralPriority::cases()),
            'status' => ReferralStatus::Received,
            'referring_party' => fake()->company(),
            'notes' => fake()->optional()->paragraph(),
            'triage_notes' => null,
            'cancelled_reason' => null,
            'cancelled_at' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(['status' => ReferralStatus::Received]);
    }

    public function triaging(): static
    {
        return $this->state(['status' => ReferralStatus::Triaging]);
    }

    public function accepted(): static
    {
        return $this->state([
            'status' => ReferralStatus::Accepted,
            'triage_notes' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state([
            'status' => ReferralStatus::Rejected,
            'triage_notes' => fake()->sentence(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => ReferralStatus::Cancelled,
            'cancelled_reason' => fake()->sentence(),
            'cancelled_at' => now(),
        ]);
    }

    public function urgent(): static
    {
        return $this->state(['priority' => ReferralPriority::Urgent]);
    }

    public function withoutIdempotencyKey(): static
    {
        return $this->state(['idempotency_key' => null]);
    }
}
