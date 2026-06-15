<?php

namespace App\Features\Analytics\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLandingVisitRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['visitor_uuid' => ['required', 'string', 'max:120'], 'session_uuid' => ['required', 'string', 'max:120'], 'path' => ['nullable', 'string', 'max:255'], 'referrer' => ['nullable', 'string', 'max:500'], 'utm_source' => ['nullable', 'string', 'max:100'], 'utm_medium' => ['nullable', 'string', 'max:100'], 'utm_campaign' => ['nullable', 'string', 'max:100'], 'device_type' => ['nullable', 'string', 'max:50'], 'browser' => ['nullable', 'string', 'max:120']]; }
}
