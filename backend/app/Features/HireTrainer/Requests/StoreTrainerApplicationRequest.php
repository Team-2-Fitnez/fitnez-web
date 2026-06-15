<?php

namespace App\Features\HireTrainer\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTrainerApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['cv' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'certificate' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], 'specialization' => ['nullable', 'string', 'max:150'], 'experience_years' => ['nullable', 'integer', 'min:0', 'max:80']]; }
}
