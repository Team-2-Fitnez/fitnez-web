<?php

namespace App\Http\Requests\Trainer;

use Illuminate\Foundation\Http\FormRequest;

class TrainerMonitoringRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->search !== null ? trim(strip_tags((string) $this->search)) : null,
            'per_page' => $this->query('per_page', $this->query('limit', 10)),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
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
