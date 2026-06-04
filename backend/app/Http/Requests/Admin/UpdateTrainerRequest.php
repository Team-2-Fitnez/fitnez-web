<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        $trainer = $this->route('trainer');
        $userId = $trainer?->user_id ?? null;

        return [
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::min(16)->mixedCase()->symbols()],
            'specialization' => ['nullable', 'string', 'max:100'],
            'biography' => ['nullable', 'string'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
