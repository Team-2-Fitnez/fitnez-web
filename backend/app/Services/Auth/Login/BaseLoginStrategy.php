<?php
namespace App\Services\Auth\Login;
use App\Models\User;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
abstract class BaseLoginStrategy {
    protected function validateUser(array $credentials, ?string $requiredRole = null): User {
        $email = strtolower(trim((string)($credentials['email'] ?? ''))); $password = (string)($credentials['password'] ?? '');
        $user = User::query()->with('role')->where('email',$email)->first();
        if(!$user || !Hash::check($password,$user->password_hash)) throw ValidationException::withMessages(['email'=>['Invalid email or password.']]);
        if(!$user->is_active) throw ValidationException::withMessages(['email'=>['This account is not active. Please verify OTP first.']]);
        if($requiredRole && $user->roleName() !== $requiredRole) throw ValidationException::withMessages(['email'=>["This login is only for {$requiredRole} users."]]);
        return $user;
    }
    protected function userPayload(User $user): array {
        $user->loadMissing(['role', 'membershipPackage']);
        $application = TrainerApplication::query()->where('user_id',$user->id)->latest('id')->first();
        $canAccessTrainerWorkspace = $application?->status === 'approved' || TrainerDetail::query()->where('user_id',$user->id)->exists();
        return [
            'id'=>$user->id,
            'email'=>$user->email,
            'full_name'=>$user->full_name,
            'phone'=>$user->phone,
            'birth_date'=>optional($user->birth_date)->toDateString(),
            'profile_picture_url'=>$user->profile_picture_url,
            'role'=>$user->roleName(),
            'is_active'=>$user->is_active,
            'email_verified_at'=>optional($user->email_verified_at)->toISOString(),
            'membership_package_id'=>$user->membership_package_id,
            'membership_started_at'=>optional($user->membership_started_at)->toISOString(),
            'membership_expires_at'=>optional($user->membership_expires_at)->toISOString(),
            'free_class_access'=>$user->free_class_access,
            'membership_package'=>$user->membershipPackage ? [
                'id'=>$user->membershipPackage->id,
                'code'=>$user->membershipPackage->code,
                'name'=>$user->membershipPackage->name,
                'duration_months'=>$user->membershipPackage->duration_months,
                'price'=>$user->membershipPackage->price,
                'free_class_access'=>$user->membershipPackage->free_class_access,
                'benefits'=>$user->membershipPackage->benefits,
                'is_active'=>$user->membershipPackage->is_active,
            ] : null,
            'trainer_status'=>$application?->status ?? 'not_submitted',
            'can_access_trainer_workspace'=>$canAccessTrainerWorkspace,
            'trainer_application_id'=>$application?->id,
        ];
    }
}
