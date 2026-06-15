<?php

namespace App\Shared\Requests\Common;

use App\Shared\Requests\Concerns\ResolvesPerPage;
use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexRequest extends FormRequest
{
    use ResolvesPerPage;

    public function authorize(): bool { return true; }
    public function rules(): array { return ['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'limit' => ['nullable', 'integer', 'min:1', 'max:100'], 'type' => ['nullable', 'string', 'max:80'], 'is_read' => ['nullable', 'boolean']]; }
}
