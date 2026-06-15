<?php

namespace App\Shared\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['trainer_id' => ['required', 'integer', 'exists:users,id'], 'member_id' => ['nullable', 'integer', 'exists:users,id'], 'scheduled_at' => ['required', 'date'], 'session_date' => ['nullable', 'date'], 'status' => ['nullable', 'string', 'max:50'], 'notes' => ['nullable', 'string', 'max:2000']]; }
}
