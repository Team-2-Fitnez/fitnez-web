<?php
namespace App\Services\Otp;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
class OtpVerifier {
    public function verify(string $email, string $plainCode, string $purpose = 'register'): User {
        $email=strtolower(trim($email));
        $otp=OtpCode::query()->where('email',$email)->where('purpose',$purpose)->whereNull('used_at')->latest('id')->first();
        if(!$otp) throw ValidationException::withMessages(['otp'=>['OTP was not found. Please request a new OTP.']]);
        if($otp->isExpired()) throw ValidationException::withMessages(['otp'=>['OTP has expired. Please request a new OTP.']]);
        if($otp->attempts>=5) throw ValidationException::withMessages(['otp'=>['Too many wrong OTP attempts. Please request a new OTP.']]);
        if(!Hash::check($plainCode,$otp->code_hash)) { $otp->increment('attempts'); throw ValidationException::withMessages(['otp'=>['Invalid OTP code.']]); }
        return DB::transaction(function() use($otp,$email) { $otp->forceFill(['used_at'=>now()])->save(); $user=$otp->user ?: User::query()->where('email',$email)->first(); if(!$user) throw ValidationException::withMessages(['email'=>['User was not found.']]); $user->forceFill(['is_active'=>true,'email_verified_at'=>now()])->save(); if(DB::getSchemaBuilder()->hasTable('authentications')) DB::table('authentications')->where('user_id',$user->id)->update(['is_active'=>true]); return $user->load('role'); });
    }
}
