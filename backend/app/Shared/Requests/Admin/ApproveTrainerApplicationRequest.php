<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTrainerApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['admin_notes' => ['nullable', 'string', 'max:2000'], 'specialization' => ['nullable', 'string', 'max:150'], 'biography' => ['nullable', 'string', 'max:2000'], 'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'], 'hourly_rate' => ['nullable', 'numeric', 'min:0']]; }
}
