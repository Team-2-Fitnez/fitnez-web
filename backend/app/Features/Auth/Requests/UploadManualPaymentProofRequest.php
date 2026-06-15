<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadManualPaymentProofRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email' => strtolower(trim((string) $this->email))]); }
    public function rules(): array { return ['registration_code' => ['required', 'string', 'max:80'], 'email' => ['required', 'email', 'max:150'], 'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120']]; }
}
