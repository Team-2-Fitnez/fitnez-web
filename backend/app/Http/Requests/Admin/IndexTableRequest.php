<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class IndexTableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->search !== null ? trim(strip_tags((string) $this->search)) : null,
            'role' => $this->role !== null ? trim((string) $this->role) : null,
            'status' => $this->status !== null ? trim((string) $this->status) : null,
            'browser' => $this->browser !== null ? trim((string) $this->browser) : null,
            'device' => $this->device !== null ? trim((string) $this->device) : null,
            'per_page' => $this->query('per_page', $this->query('limit', 10)),
        ]);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:50'],
            'browser' => ['nullable', 'string', 'max:100'],
            'device' => ['nullable', 'string', 'max:100'],
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
