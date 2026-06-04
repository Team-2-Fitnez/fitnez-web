<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\TrainerApplication;
use App\Models\TrainerDetail;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $adminRole = Role::query()->firstOrCreate(
            ['name' => 'admin'],
            ['description' => 'Fitnez administrator']
        );

        $memberRole = Role::query()->firstOrCreate(
            ['name' => 'member'],
            ['description' => 'Fitnez member/user']
        );

        $trainerRole = Role::query()->firstOrCreate(
            ['name' => 'trainer'],
            ['description' => 'Fitnez personal trainer']
        );


        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@fitnez.test'],
            [
                'full_name' => 'Fitnez Admin',
                'phone' => '080000000001',
                'role_id' => $adminRole->id,
                'password_hash' => Hash::make('FitnezTeam2@2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $trainerUser = User::query()->updateOrCreate(
            ['email' => 'trainer@fitnez.test'],
            [
                'full_name' => 'Fitnez Approved Trainer',
                'phone' => '080000000002',
                'role_id' => $trainerRole->id,
                'password_hash' => Hash::make('FitnezTeam2@2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'member@fitnez.test'],
            [
                'full_name' => 'Fitnez Member',
                'phone' => '080000000003',
                'role_id' => $memberRole->id,
                'password_hash' => Hash::make('FitnezTeam2@2026'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        TrainerApplication::query()->updateOrCreate(
            ['user_id' => $trainerUser->id, 'status' => 'approved'],
            [
                'cv_document_url' => 'dummy/approved-trainer-cv.pdf',
                'certificate_document_url' => 'dummy/approved-trainer-certificate.pdf',
                'submitted_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by_admin_id' => $admin->id,
                'admin_notes' => 'Dummy trainer account for team testing.',
            ]
        );

        TrainerDetail::query()->updateOrCreate(
            ['user_id' => $trainerUser->id],
            [
                'specialization' => 'Strength Training',
                'biography' => 'Dummy approved trainer account for Fitnez testing.',
                'experience_years' => 3,
                'hourly_rate' => 150000,
                'avg_rating' => 4.8,
            ]
        );

        // Seed FAQs
        $faqs = [
            [
                'question' => 'How do I register as a Fitnez Gym member?',
                'answer' => 'You can register directly through this website on the registration page. Simply fill in your personal data, choose a membership package (1 Month, 3 Months, 6 Months, or 12 Months), choose a payment method, perform the transfer, and upload your payment proof. Our admin will verify and activate your account within a maximum of 24 hours.',
                'category' => 'General',
                'sort_order' => 1,
            ],
            [
                'question' => 'Can I cancel my membership after registering?',
                'answer' => 'Purchased memberships cannot be cancelled or refunded. However, you can choose not to renew your membership at the end of your package validity period.',
                'category' => 'General',
                'sort_order' => 2,
            ],
            [
                'question' => 'What is the difference between membership packages?',
                'answer' => '1-Month & 3-Month packages provide full access to the gym. 6-Month & 12-Month packages provide full access to the gym plus free access to special classes like Yoga and Aerobics without additional fees.',
                'category' => 'Package',
                'sort_order' => 3,
            ],
            [
                'question' => 'Is there an initial registration fee (admin fee)?',
                'answer' => 'There are no hidden additional fees. You only pay the price of the package you choose for the active duration of that package.',
                'category' => 'Package',
                'sort_order' => 4,
            ],
            [
                'question' => 'What payment methods are supported?',
                'answer' => 'We support easy manual payment methods via QRIS (scan the barcode from your e-wallet/mobile banking) or direct bank transfer to the official Fitnez Gym BCA account.',
                'category' => 'Payment',
                'sort_order' => 5,
            ],
            [
                'question' => 'How long does the payment verification process take after the transfer proof is uploaded?',
                'answer' => 'The verification process usually takes less than 1 hour during operational hours (08:00 - 21:00 WIB), with a maximum limit of 24 hours.',
                'category' => 'Payment',
                'sort_order' => 6,
            ],
            [
                'question' => 'How do I hire a Personal Trainer (PT)?',
                'answer' => 'Once your registration is approved, please log in to your Member Area. There, select the "Hire a Trainer" menu to see our list of certified trainers, their profiles, specializations, hourly rates, and apply for a hire online.',
                'category' => 'Trainer',
                'sort_order' => 7,
            ],
            [
                'question' => 'Are there locker and shower facilities at Fitnez Gym?',
                'answer' => 'Yes, we provide high-security free lockers, separate changing rooms, and clean showers equipped with hot water for all active members.',
                'category' => 'General',
                'sort_order' => 8,
            ],
            [
                'question' => 'Can I freeze my membership if I am sick or traveling?',
                'answer' => 'Yes, specifically for holders of 6-Month (Plus) and 12-Month (Premium) packages, you get free membership freezing facilities for a maximum of 14 days (for the 6-month package) or 30 days (for the 12-month package) by submitting supporting evidence to the admin.',
                'category' => 'Package',
                'sort_order' => 9,
            ],
            [
                'question' => 'Are training programs and diet plans (workout & meal plan) individually customized?',
                'answer' => 'Of course! Your Personal Trainer will create a training program and nutrition guide exclusively customized based on your body shape, fitness goals (weight loss, bulking, etc.), health history, and food preferences.',
                'category' => 'Trainer',
                'sort_order' => 10,
            ],
            [
                'question' => 'How do I extend my membership validity period?',
                'answer' => 'Before your package expires, you will receive a notification in your Member Area. You simply select the Extend Package menu, transfer the fee corresponding to your new package choice, and upload the payment proof on that extension page.',
                'category' => 'Payment',
                'sort_order' => 11,
            ],
            [
                'question' => 'What are the operational hours of Fitnez Gym?',
                'answer' => 'We are open daily from 06:00 to 22:00 WIB on Mondays to Fridays, and 07:00 to 20:00 WIB on Saturdays, Sundays, and National Holidays.',
                'category' => 'General',
                'sort_order' => 12,
            ],
        ];

        foreach ($faqs as $faq) {
            \App\Models\Faq::query()->updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'category' => $faq['category'],
                    'sort_order' => $faq['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
