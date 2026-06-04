<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest;
class UploadManualPaymentProofRequest extends FormRequest {
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email'=>strtolower(trim((string)$this->email)),'registration_code'=>trim((string)$this->registration_code)]); }
    public function rules(): array { return ['registration_code'=>['required','string','max:120'],'email'=>['required','email:rfc'],'payment_proof'=>['required','image','mimes:jpg,jpeg,png,webp','max:4096']]; }
}
