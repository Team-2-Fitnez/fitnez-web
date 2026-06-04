<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyLoginOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->email)),
            'channel' => $this->channel ?: 'web',
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc'],
            'otp' => ['required', 'digits:6'],
            'channel' => ['nullable', 'in:web,mobile'],
        ];
    }
}
