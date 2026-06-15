<?php

namespace App\Http\Requests\Common;

use Illuminate\Foundation\Http\FormRequest;

class PublicListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'limit' => $this->query('limit', $this->query('per_page')),
        ]);
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function limit(int $default = 20): int
    {
        $value = $this->validated('limit') ?? $this->validated('per_page') ?? $default;

        return min(max((int) $value, 1), 100);
    }
}
