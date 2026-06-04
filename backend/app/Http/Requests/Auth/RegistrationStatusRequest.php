<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegistrationStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->query('email'))),
            'registration_code' => trim((string) $this->query('registration_code')),
        ]);
    }

    public function rules(): array
    {
        return [
            'registration_code' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
        ];
    }
}
