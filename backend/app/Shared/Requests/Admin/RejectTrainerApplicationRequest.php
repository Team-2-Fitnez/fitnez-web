<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class RejectTrainerApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['admin_notes' => ['required', 'string', 'max:2000']]; }
}
