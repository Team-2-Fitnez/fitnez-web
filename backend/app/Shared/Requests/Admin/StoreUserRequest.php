<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['full_name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150', 'unique:users,email'], 'phone' => ['nullable', 'string', 'max:30'], 'role_id' => ['required', 'integer', 'exists:roles,id'], 'password' => ['required', 'string', 'min:8'], 'is_active' => ['required', 'boolean']]; }
}
