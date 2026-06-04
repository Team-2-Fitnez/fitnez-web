<?php
namespace App\Http\Requests\Auth;
use Illuminate\Foundation\Http\FormRequest; use Illuminate\Validation\Rules\Password;
class StartManualProspectiveRegistrationRequest extends FormRequest {
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void { $this->merge(['email'=>strtolower(trim((string)$this->email)),'full_name'=>trim((string)$this->full_name),'phone'=>$this->phone?trim((string)$this->phone):null]); }
    public function rules(): array { return [
        'full_name'=>['required','string','min:3','max:100'], 'email'=>['required','email:rfc','max:255','unique:users,email'], 'phone'=>['nullable','string','min:8','max:20'],
        'password'=>['required','confirmed',Password::min(16)->mixedCase()->symbols()], 'membership_package_id'=>['required','exists:membership_packages,id'], 'manual_payment_method_id'=>['required','exists:manual_payment_methods,id'],
    ]; }
}
