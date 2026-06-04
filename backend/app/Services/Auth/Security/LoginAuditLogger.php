<?php
namespace App\Services\Auth\Security;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class LoginAuditLogger {
    public function success(?int $userId, Request $request): void { $this->write($userId,'LOGIN_SUCCESS','User logged in',$request); }
    public function failed(?int $userId, Request $request, string $reason): void { $this->write($userId,'LOGIN_FAILED',$reason,$request); }
    private function write(?int $userId, string $action, string $description, Request $request): void {
        if(!DB::getSchemaBuilder()->hasTable('system_logs')) return;
        DB::table('system_logs')->insert([
            'user_id'=>$userId,
            'action_type'=>$action,
            'table_affected'=>'users',
            'record_id'=>$userId,
            'description'=>$description,
            'ip_address'=>$request->ip(),
            'user_agent'=>$request->userAgent(),
            'metadata'=>json_encode(['email'=>strtolower((string)$request->input('email'))]),
            'created_at'=>now(),
        ]);
    }
}
