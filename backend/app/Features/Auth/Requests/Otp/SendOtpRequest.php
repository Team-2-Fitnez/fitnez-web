<?php

namespace App\Features\Auth\Requests\Otp;

use Illuminate\Foundation\Http\FormRequest;

class SendOtpRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email' => strtolower(trim((string) $this->email)), 'purpose' => $this->input('purpose', 'register')]); }
    public function rules(): array { return ['email' => ['required', 'email', 'max:150'], 'purpose' => ['nullable', 'string', 'max:50']]; }
}
