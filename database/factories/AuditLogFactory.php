<?php

namespace Database\Factories;

use App\Enums\AuditEvent;
use App\Models\Referral;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AuditLog>
 */
class AuditLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'referral_id' => Referral::factory(),
            'event' => fake()->randomElement(AuditEvent::cases()),
            'metadata' => null,
            'created_at' => now(),
        ];
    }
}
