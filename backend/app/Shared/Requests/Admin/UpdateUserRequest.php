<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { $userId = $this->route('user')?->id ?? $this->route('user'); return ['full_name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)], 'phone' => ['nullable', 'string', 'max:30'], 'role_id' => ['required', 'integer', 'exists:roles,id'], 'password' => ['nullable', 'string', 'min:8'], 'is_active' => ['required', 'boolean']]; }
}
