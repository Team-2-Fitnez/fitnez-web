<?php

namespace App\Actions\Auth;

use App\Models\ManualPaymentMethod;
use App\Models\MembershipPackage;
use App\Models\ProspectiveMemberRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StartManualProspectiveRegistrationAction
{
    public function handle(array $data): ProspectiveMemberRegistration
    {
        $email = strtolower(trim($data['email']));

        if (User::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered.'],
            ]);
        }

        $package = MembershipPackage::query()
            ->where('id', $data['membership_package_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $method = ManualPaymentMethod::query()
            ->where('id', $data['manual_payment_method_id'])
            ->where('is_active', true)
            ->firstOrFail();

        $registrationCode = 'FITNEZ-MANUAL-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6));

        $payload = [
            'membership_package_id' => $package->id,
            'manual_payment_method_id' => $method->id,
            'registration_code' => $registrationCode,
            'full_name' => trim($data['full_name']),
            'email' => $email,
            'phone' => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'amount' => $package->price,
            'status' => 'awaiting_payment',
        ];

        return ProspectiveMemberRegistration::query()
            ->create($payload)
            ->load(['package', 'paymentMethod']);
    }
}
