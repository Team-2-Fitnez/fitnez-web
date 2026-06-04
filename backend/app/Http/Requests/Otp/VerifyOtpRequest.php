<?php

namespace App\Http\Requests\Otp;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->email)),
            'otp' => trim((string) $this->otp),
            'purpose' => $this->purpose ? trim((string) $this->purpose) : 'register',
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
            'otp' => ['required', 'digits:6'],
            'purpose' => ['nullable', 'in:register,password_reset'],
        ];
    }
}
