<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MemberPaymentAttendanceReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->search !== null ? trim(strip_tags((string) $this->search)) : null,
            'payment_status' => $this->payment_status !== null ? trim((string) $this->payment_status) : null,
            'payment_type' => $this->payment_type !== null ? trim((string) $this->payment_type) : null,
            'attendance_type' => $this->attendance_type !== null ? trim((string) $this->attendance_type) : null,
            'start_date' => $this->start_date !== null ? trim((string) $this->start_date) : null,
            'end_date' => $this->end_date !== null ? trim((string) $this->end_date) : null,
            'per_page' => $this->query('per_page', $this->query('limit', 10)),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'payment_type' => ['nullable', 'string', 'max:50'],
            'attendance_type' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function perPage(int $default = 10): int
    {
        return min(max((int) ($this->validated('per_page') ?? $default), 1), 100);
    }
}
