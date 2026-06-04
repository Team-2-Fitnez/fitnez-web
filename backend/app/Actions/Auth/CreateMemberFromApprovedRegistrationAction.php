<?php
namespace App\Actions\Auth;
use App\Models\ProspectiveMemberRegistration; use App\Models\Role; use App\Models\User; use Illuminate\Support\Facades\DB;
class CreateMemberFromApprovedRegistrationAction {
    public function handle(ProspectiveMemberRegistration $registration): User {
        if ($registration->user_id) return $registration->user;
        return DB::transaction(function() use ($registration) {
            $registration->loadMissing('package');
            $role=Role::query()->firstOrCreate(['name'=>'member'],['description'=>'Fitnez member']);
            $user=User::query()->create([
                'full_name'=>$registration->full_name,'email'=>$registration->email,'phone'=>$registration->phone,
                'password_hash'=>$registration->password_hash,'role_id'=>$role->id,'is_active'=>true,'email_verified_at'=>now(),
                'membership_package_id'=>$registration->package->id,'membership_started_at'=>now(),
                'membership_expires_at'=>now()->addMonths($registration->package->duration_months),
                'free_class_access'=>$registration->package->free_class_access,
            ]);
            if (DB::getSchemaBuilder()->hasTable('authentications')) DB::table('authentications')->insert(['user_id'=>$user->id,'email'=>$user->email,'password_hash'=>$user->password_hash,'provider'=>'local','is_active'=>true,'failed_login_attempts'=>0,'password_updated_at'=>now(),'last_login'=>null]);
            $registration->update(['user_id'=>$user->id,'account_created_at'=>now()]);
            return $user;
        });
    }
}
