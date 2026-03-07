<?php

namespace App\Http\Requests;

use App\Enums\ReferralPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateReferralRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'patient_name' => ['required', 'string', 'max:255'],
            'patient_date_of_birth' => ['required', 'date', 'before:today'],
            'patient_external_id' => ['nullable', 'string', 'max:100'],
            'referral_reason' => ['required', 'string', 'max:2000'],
            'priority' => ['required', 'string', Rule::enum(ReferralPriority::class)],
            'referring_party' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'patient_name.required' => 'Patient name is required.',
            'patient_date_of_birth.required' => 'Patient date of birth is required.',
            'patient_date_of_birth.date' => 'Patient date of birth must be a valid date.',
            'patient_date_of_birth.before' => 'Patient date of birth must be in the past.',
            'referral_reason.required' => 'A referral reason is required.',
            'priority.required' => 'A priority level is required.',
            'priority.enum' => 'Priority must be one of: low, medium, high, urgent.',
            'referring_party.required' => 'The referring party is required.',
        ];
    }
}
