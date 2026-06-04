<?php
namespace App\Observers;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class UserObserver { public function created(User $user): void { $this->log($user->id,'USER_CREATED','User account created.'); } public function updated(User $user): void { if($user->wasChanged('email_verified_at')) $this->log($user->id,'USER_EMAIL_VERIFIED','User email verified through OTP.'); } private function log(?int $userId,string $action,string $desc): void { if(!DB::getSchemaBuilder()->hasTable('system_logs')) return; DB::table('system_logs')->insert(['user_id'=>$userId,'action_type'=>$action,'table_affected'=>'users','record_id'=>$userId,'description'=>$desc,'created_at'=>now()]); } }
