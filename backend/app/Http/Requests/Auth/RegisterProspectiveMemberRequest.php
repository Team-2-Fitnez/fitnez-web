<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterProspectiveMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => strtolower(trim((string) $this->email)),
            'full_name' => trim((string) $this->full_name),
            'phone' => $this->phone ? trim((string) $this->phone) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => [
                'required',
                'string',
                'min:3',
                'max:100',
                'regex:/^[a-zA-Z\s.\'-]+$/',
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:rfc',
                'max:255',
                'unique:users,email',
            ],
            'phone' => [
                'nullable',
                'string',
                'min:8',
                'max:20',
                'regex:/^[0-9+\-\s]+$/',
            ],
            'password' => [
                'required',
                'confirmed',
                Password::min(16)->mixedCase()->symbols(),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.regex' => 'Full name may only contain letters, spaces, dots, apostrophes, and hyphens.',
            'email.email' => 'Email address is not valid.',
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Phone number may only contain numbers, spaces, plus sign, and hyphen.',
            'password.min' => 'Password must be at least 16 characters.',
            'password.mixed' => 'Password must contain uppercase and lowercase letters.',
            'password.symbols' => 'Password must contain at least one special character.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];
    }
}
