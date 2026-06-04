<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreTrainerRequest extends FormRequest
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
        return [
            'full_name' => ['required', 'string', 'min:3', 'max:100'],
            'email' => ['required', 'email:rfc', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::min(16)->mixedCase()->symbols()],
            'specialization' => ['nullable', 'string', 'max:100'],
            'biography' => ['nullable', 'string'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
