<?php
namespace App\Observers;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Log;
class OtpCodeObserver { public function created(OtpCode $otp): void { Log::info('FITNEZ_OTP_CREATED',['email'=>$otp->email,'purpose'=>$otp->purpose,'expires_at'=>$otp->expires_at?->toISOString()]); } public function updated(OtpCode $otp): void { if($otp->wasChanged('used_at')) Log::info('FITNEZ_OTP_USED',['email'=>$otp->email,'purpose'=>$otp->purpose]); if($otp->wasChanged('attempts')) Log::warning('FITNEZ_OTP_ATTEMPT_FAILED',['email'=>$otp->email,'purpose'=>$otp->purpose,'attempts'=>$otp->attempts]); } }
