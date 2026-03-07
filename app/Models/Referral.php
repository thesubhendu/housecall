<?php

namespace App\Models;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    use HasFactory;
    use HasUlids;

    protected $fillable = [
        'idempotency_key',
        'patient_name',
        'patient_date_of_birth',
        'patient_external_id',
        'referral_reason',
        'priority',
        'status',
        'referring_party',
        'notes',
        'triage_notes',
        'cancelled_reason',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => ReferralPriority::class,
            'status' => ReferralStatus::class,
            'patient_date_of_birth' => 'date',
            'cancelled_at' => 'datetime',
        ];
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderBy('created_at');
    }
}
