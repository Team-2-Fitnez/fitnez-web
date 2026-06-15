<?php

namespace App\Shared\Requests\Concerns;

trait ResolvesPerPage
{
    public function perPage(int $default = 10): int
    {
        $value = $this->validated('per_page') ?? $this->validated('limit') ?? $default;

        return min(max((int) $value, 1), 100);
    }
}
