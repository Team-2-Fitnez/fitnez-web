<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:users,id'],
            'trainer_id' => ['required', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'sessions_per_week' => ['required', 'integer', 'in:3,5,7'],
            'session_days' => ['required', 'array', 'min:1', 'max:7'],
            'session_days.*' => ['required', 'string', 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'session_time' => ['required', 'date_format:H:i'],
            'member_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:pending,pending_payment,confirmed,completed,cancelled'],
            'base_price_per_session' => ['required', 'numeric', 'min:0'],
            'member_price_per_session' => ['required', 'numeric', 'min:0'],
            'total_member_price' => ['required', 'numeric', 'min:0'],
            'total_trainer_price' => ['required', 'numeric', 'min:0'],
            'total_sessions' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
