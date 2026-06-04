<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class IncomingRentHistoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->search !== null ? trim(strip_tags((string) $this->search)) : null,
            'status' => $this->status !== null ? trim((string) $this->status) : null,
            'start_date' => $this->start_date !== null ? trim((string) $this->start_date) : null,
            'end_date' => $this->end_date !== null ? trim((string) $this->end_date) : null,
            'per_page' => $this->query('per_page', $this->query('limit', 10)),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
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
