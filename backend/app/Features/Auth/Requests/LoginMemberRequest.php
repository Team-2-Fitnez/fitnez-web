<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email' => strtolower(trim((string) $this->email)), 'channel' => $this->input('channel', 'web')]); }
    public function rules(): array { return ['email' => ['required', 'email', 'max:150'], 'password' => ['required', 'string'], 'channel' => ['nullable', 'in:web,mobile']]; }
}
