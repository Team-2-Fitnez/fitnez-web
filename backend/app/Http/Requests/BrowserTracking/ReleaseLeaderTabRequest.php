<?php
namespace App\Http\Requests\BrowserTracking;
use Illuminate\Foundation\Http\FormRequest;
class ReleaseLeaderTabRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['device_uuid'=>['required','string','max:120'],'tab_id'=>['required','string','max:120'],'context_type'=>['nullable','string','max:30']]; }
}
