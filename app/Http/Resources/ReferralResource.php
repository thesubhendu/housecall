<?php

namespace App\Http\Resources;

use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Referral
 */
final class ReferralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status->value,
            'priority' => $this->priority->value,
            'patient' => [
                'name' => $this->patient_name,
                'date_of_birth' => $this->patient_date_of_birth->toDateString(),
                'external_id' => $this->patient_external_id,
            ],
            'referral_reason' => $this->referral_reason,
            'referring_party' => $this->referring_party,
            'notes' => $this->notes,
            'triage_notes' => $this->triage_notes,
            'cancelled_reason' => $this->cancelled_reason,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
