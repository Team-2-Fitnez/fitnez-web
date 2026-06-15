<?php

namespace App\Features\Payments\Services\Membership;

class MembershipPackageFactory
{
    public static function defaultPackages(): array
    {
        return [
            [
                'code' => 'PKG_1_MONTH_BASIC',
                'name' => '1 Month Basic',
                'duration_months' => 1,
                'price' => 150000,
                'free_class_access' => false,
                'benefits' => [
                    'Gym access for 1 month',
                    'Workout and meal plan access',
                    'Member attendance tracking',
                ],
            ],
            [
                'code' => 'PKG_3_MONTHS_STANDARD',
                'name' => '3 Months Standard',
                'duration_months' => 3,
                'price' => 400000,
                'free_class_access' => true,
                'benefits' => [
                    'Gym access for 3 months',
                    'Workout and meal plan access',
                    'Free class access',
                    'Priority member support',
                ],
            ],
            [
                'code' => 'PKG_12_MONTHS_PREMIUM',
                'name' => '12 Months Premium',
                'duration_months' => 12,
                'price' => 1200000,
                'free_class_access' => true,
                'benefits' => [
                    'Gym access for 12 months',
                    'Workout and meal plan access',
                    'Free class access',
                    'Best value yearly membership',
                ],
            ],
        ];
    }
}
