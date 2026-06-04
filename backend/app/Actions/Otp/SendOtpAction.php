<?php
namespace App\Actions\Otp;
use App\Models\User;
use App\Services\Otp\OtpManager;
use Illuminate\Http\Request;
class SendOtpAction { public function __construct(private readonly OtpManager $otp) {} public function handle(array $data, Request $request): array { $email=strtolower(trim($data['email'])); $purpose=$data['purpose'] ?? 'register'; $user=User::query()->where('email',$email)->first(); $user ? $this->otp->sendForUser($user,$purpose,$request->ip()) : $this->otp->sendToEmail($email,$purpose,$request->ip()); return ['email'=>$email,'purpose'=>$purpose]; } }
