<?php
namespace App\Http\Requests\BrowserTracking;
use Illuminate\Foundation\Http\FormRequest;
class BrowserHeartbeatRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['device_uuid'=>['required','string','max:120'],'tab_id'=>['required','string','max:120'],'context_type'=>['nullable','string','max:30'],'platform'=>['nullable','string','max:40'],'browser'=>['nullable','string','max:120'],'user_agent_hash'=>['nullable','string','max:128'],'route'=>['nullable','string','max:255']]; }
}
