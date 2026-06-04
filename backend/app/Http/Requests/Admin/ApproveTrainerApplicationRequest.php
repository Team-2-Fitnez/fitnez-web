<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTrainerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'specialization' => ['nullable', 'string', 'max:100'],
            'biography' => ['nullable', 'string', 'max:2000'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:80'],
            'hourly_rate' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ];
    }
}
