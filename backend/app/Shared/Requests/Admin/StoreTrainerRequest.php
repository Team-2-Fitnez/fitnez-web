<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainerRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['full_name' => ['required', 'string', 'max:150'], 'email' => ['required', 'email', 'max:150', 'unique:users,email'], 'phone' => ['nullable', 'string', 'max:30'], 'password' => ['required', 'string', 'min:8'], 'specialization' => ['nullable', 'string', 'max:150'], 'biography' => ['nullable', 'string', 'max:2000'], 'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'], 'hourly_rate' => ['nullable', 'numeric', 'min:0']]; }
}
