<?php
namespace App\Actions\Auth;
use App\Models\Role;
use App\Models\User;
use App\Services\Otp\OtpManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class RegisterProspectiveMemberAction {
    public function __construct(private readonly OtpManager $otp) {}
    public function handle(array $data, Request $request): array {
        $email=strtolower(trim($data['email'])); if(User::query()->where('email',$email)->exists()) throw ValidationException::withMessages(['email'=>['Email is already registered.']]);
        $role=Role::query()->firstOrCreate(['name'=>'member'],['description'=>'Fitnez member']);
        return DB::transaction(function() use($data,$request,$email,$role) { $user=User::query()->create(['full_name'=>$data['full_name'],'email'=>$email,'phone'=>$data['phone'] ?? null,'password_hash'=>Hash::make($data['password']),'role_id'=>$role->id,'is_active'=>false]); if(DB::getSchemaBuilder()->hasTable('authentications')) DB::table('authentications')->insert(['user_id'=>$user->id,'email'=>$user->email,'password_hash'=>$user->password_hash,'provider'=>'local','is_active'=>false,'failed_login_attempts'=>0,'password_updated_at'=>now(),'last_login'=>null]); $this->otp->sendForUser($user,'register',$request->ip()); return ['user'=>['id'=>$user->id,'email'=>$user->email,'full_name'=>$user->full_name,'role'=>'member','is_active'=>false],'next_step'=>'verify_otp']; });
    }
}
