<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StartManualProspectiveRegistrationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email' => strtolower(trim((string) $this->email))]); }
    public function rules(): array { return ['membership_package_id' => ['required', 'integer', 'exists:membership_packages,id'], 'manual_payment_method_id' => ['required', 'integer', 'exists:manual_payment_methods,id'], 'full_name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150'], 'phone' => ['nullable', 'string', 'max:30'], 'birth_date' => ['nullable', 'date'], 'password' => ['required', 'string', 'min:8', 'confirmed']]; }
}
