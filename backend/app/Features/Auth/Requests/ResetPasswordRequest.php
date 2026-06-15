<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email' => strtolower(trim((string) $this->email))]); }
    public function rules(): array { return ['email' => ['required', 'email', 'max:150'], 'otp' => ['required', 'string', 'size:6'], 'password' => ['required', 'string', 'min:8', 'confirmed']]; }
}
