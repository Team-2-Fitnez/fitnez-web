<?php
namespace App\Features\HireTrainer\Requests\Admin;
use Illuminate\Foundation\Http\FormRequest;
class RejectProspectiveMemberRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return ['reason'=>['required','string','max:1000']]; } }
