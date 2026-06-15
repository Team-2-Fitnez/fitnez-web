<?php

namespace App\Features\Auth\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['full_name' => ['required', 'string', 'max:150'], 'age' => ['nullable', 'integer', 'min:1', 'max:120'], 'phone' => ['nullable', 'string', 'max:30']]; }
}
