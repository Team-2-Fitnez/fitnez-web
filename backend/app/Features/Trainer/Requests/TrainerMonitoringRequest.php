<?php

namespace App\Features\Trainer\Requests;

use App\Shared\Requests\Concerns\ResolvesPerPage;
use Illuminate\Foundation\Http\FormRequest;

class TrainerMonitoringRequest extends FormRequest
{
    use ResolvesPerPage;

    public function authorize(): bool { return true; }
    public function rules(): array { return ['search' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:50'], 'page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:100'], 'limit' => ['nullable', 'integer', 'min:1', 'max:100']]; }
}
