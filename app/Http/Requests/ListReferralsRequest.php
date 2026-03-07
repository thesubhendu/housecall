<?php

namespace App\Http\Requests;

use App\Enums\ReferralPriority;
use App\Enums\ReferralStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListReferralsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'string', Rule::enum(ReferralStatus::class)],
            'priority' => ['nullable', 'string', Rule::enum(ReferralPriority::class)],
            'referring_party' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
