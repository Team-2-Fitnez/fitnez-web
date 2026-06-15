<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTrainerRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { $trainer = $this->route('trainer'); $userId = is_object($trainer) ? $trainer->user_id : null; return ['full_name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId)], 'phone' => ['nullable', 'string', 'max:30'], 'password' => ['nullable', 'string', 'min:8'], 'is_active' => ['required', 'boolean'], 'specialization' => ['nullable', 'string', 'max:150'], 'biography' => ['nullable', 'string', 'max:2000'], 'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'], 'hourly_rate' => ['nullable', 'numeric', 'min:0']]; }
}
