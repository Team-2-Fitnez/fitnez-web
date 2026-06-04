<?php

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandingVisitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'visitor_uuid' => trim((string) $this->visitor_uuid),
            'session_uuid' => trim((string) $this->session_uuid),
            'route_path' => $this->route_path ? trim((string) $this->route_path) : '/',
        ]);
    }

    public function rules(): array
    {
        return [
            'visitor_uuid' => ['required', 'string', 'max:120'],
            'session_uuid' => ['required', 'string', 'max:120'],
            'referrer' => ['nullable', 'string', 'max:2000'],
            'landing_url' => ['nullable', 'string', 'max:2000'],
            'route_path' => ['nullable', 'string', 'max:255'],
            'query_params' => ['nullable', 'array'],
            'locale' => ['nullable', 'string', 'max:30'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'screen_width' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'screen_height' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'viewport_width' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'viewport_height' => ['nullable', 'integer', 'min:0', 'max:100000'],
	    'browser_context' => ['nullable', 'string', 'max:80'],
	    'browser_context_label' => ['nullable', 'string', 'max:120'],
	    'client_browser_name' => ['nullable', 'string', 'max:80'],
	    'client_browser_engine' => ['nullable', 'string', 'max:80'],
	    'private_mode_detected' => ['nullable', 'boolean'],
	    'private_mode_confidence' => ['nullable', 'string', 'max:40'],
	    'private_mode_source' => ['nullable', 'string', 'max:80'],
        ];
    }
}
