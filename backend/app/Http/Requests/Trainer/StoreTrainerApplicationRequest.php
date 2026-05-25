<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainerApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cv' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'certificate' => ['required', 'file', 'mimes:pdf', 'max:5120'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
        ];
    }
}
