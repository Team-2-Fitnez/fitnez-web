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
            'booking_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'session_type' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'member_notes' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'max:255'],
            'total_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
