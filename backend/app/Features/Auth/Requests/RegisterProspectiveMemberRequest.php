<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterProspectiveMemberRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email' => strtolower(trim((string) $this->email))]); }
    public function rules(): array { return ['full_name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'password' => ['required', 'string', 'min:8', 'confirmed']]; }
}
