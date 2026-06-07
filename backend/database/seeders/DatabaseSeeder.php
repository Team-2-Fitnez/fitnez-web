<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Role;
use App\Models\TrainerApplication;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\TrainerEarning;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Core Roles
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

        // 2. Clear previous transaction data to ensure exact count of 100 dummy records
        DB::table('chat_messages')->delete();
        DB::table('food_logs')->delete();
        DB::table('class_members')->delete();
        DB::table('classes')->delete();
        DB::table('attendance')->delete();
        DB::table('trainer_earnings')->delete();
        DB::table('payments')->delete();
        DB::table('trainer_bookings')->delete();
        DB::table('trainer_details')->delete();
        DB::table('trainer_applications')->delete();
        DB::table('workout_trackings')->delete();
        DB::table('workout_exercises')->delete();
        DB::table('workout_plans')->delete();
        DB::table('meal_plans')->delete();
        DB::table('meals')->delete();
        DB::table('nutrition_calculator')->delete();
        DB::table('prospective_member_registrations')->delete();
        DB::table('notifications')->delete();
        DB::table('user_devices')->delete();
        DB::table('browser_tabs')->delete();
        DB::table('jwt_sessions')->delete();
        DB::table('otp_codes')->delete();
        
        // Delete all users EXCEPT the admin@fitnez.test if it exists, otherwise delete all
        User::query()->where('email', '!=', 'admin@fitnez.test')->delete();

        // 3. Create/Update Admin Account
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

        if (DB::getSchemaBuilder()->hasTable('authentications')) {
            DB::table('authentications')->updateOrInsert(
                ['email' => 'admin@fitnez.test'],
                [
                    'user_id' => $admin->id,
                    'password_hash' => $admin->password_hash,
                    'provider' => 'local',
                    'is_active' => true,
                    'failed_login_attempts' => 0,
                    'password_updated_at' => now()
                ]
            );
        }

        // 4. Retrieve Packages and Payment Methods
        $packages = \App\Models\MembershipPackage::all();
        if ($packages->isEmpty()) {
            foreach (\App\Services\Membership\MembershipPackageFactory::defaultPackages() as $p) {
                \App\Models\MembershipPackage::query()->updateOrCreate(['code' => $p['code']], [
                    'name' => $p['name'],
                    'duration_months' => $p['duration_months'],
                    'price' => $p['price'],
                    'free_class_access' => $p['free_class_access'],
                    'benefits' => $p['benefits'],
                    'is_active' => true
                ]);
            }
            $packages = \App\Models\MembershipPackage::all();
        }

        $paymentMethods = \App\Models\ManualPaymentMethod::all();
        if ($paymentMethods->isEmpty()) {
            \App\Models\ManualPaymentMethod::query()->updateOrCreate(['code' => 'QRIS_FITNEZ'], [
                'type' => 'qris',
                'display_name' => 'QRIS Fitnez',
                'account_name' => 'FITNEZ GYM',
                'qris_image_url' => '/images/payment/qris-fitnez-placeholder.svg',
                'instructions' => 'Scan QRIS, input the package price manually, then upload proof.',
                'is_active' => true
            ]);
            \App\Models\ManualPaymentMethod::query()->updateOrCreate(['code' => 'BANK_TRANSFER_BCA'], [
                'type' => 'bank_transfer',
                'display_name' => 'Bank Transfer BCA',
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'FITNEZ GYM',
                'instructions' => 'Transfer the package price to this account, then upload proof.',
                'is_active' => true
            ]);
            $paymentMethods = \App\Models\ManualPaymentMethod::all();
        }

        $qrisMethod = $paymentMethods->where('code', 'QRIS_FITNEZ')->first();
        $bankMethod = $paymentMethods->where('code', 'BANK_TRANSFER_BCA')->first();

        // 5. Seed FAQs
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

        // 6. User Creation Helper
        $createUserWithRegistration = function(
            string $email,
            string $fullName,
            string $phone,
            string $statusType, // 'active', 'expiring', 'expired'
            $package,
            $paymentMethod,
            $role
        ) use ($admin) {
            $now = now();
            
            // Calculate starting & ending dates based on condition
            if ($statusType === 'expiring') {
                // Started 28 days ago, expires in 2 days (for 1 month package)
                $startedAt = $now->copy()->subDays(28);
                $expiresAt = $now->copy()->addDays(2);
            } elseif ($statusType === 'expired') {
                // Started 45 days ago, expired 15 days ago
                $startedAt = $now->copy()->subDays(45);
                $expiresAt = $now->copy()->subDays(15);
            } else {
                // Active: started randomly between 5 and 20 days ago
                $daysAgo = rand(5, 20);
                $startedAt = $now->copy()->subDays($daysAgo);
                $expiresAt = $startedAt->copy()->addMonths($package->duration_months);
            }

            $passwordHash = Hash::make('FitnezTeam2@2026');

            // User record
            $user = User::query()->create([
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'role_id' => $role->id,
                'password_hash' => $passwordHash,
                'is_active' => true,
                'email_verified_at' => $startedAt,
                'membership_package_id' => $package->id,
                'membership_started_at' => $startedAt,
                'membership_expires_at' => $expiresAt,
                'free_class_access' => $package->free_class_access,
            ]);

            // Authentication record (local login support)
            if (DB::getSchemaBuilder()->hasTable('authentications')) {
                DB::table('authentications')->insert([
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'password_hash' => $user->password_hash,
                    'provider' => 'local',
                    'is_active' => true,
                    'failed_login_attempts' => 0,
                    'password_updated_at' => $startedAt,
                    'last_login' => null
                ]);
            }

            // Prospective Member Registration record
            \App\Models\ProspectiveMemberRegistration::query()->create([
                'membership_package_id' => $package->id,
                'manual_payment_method_id' => $paymentMethod->id,
                'user_id' => $user->id,
                'admin_id' => $admin->id,
                'registration_code' => 'REG-' . strtoupper(bin2hex(random_bytes(4))),
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'password_hash' => $passwordHash,
                'amount' => $package->price,
                'status' => 'approved',
                'payment_proof_path' => 'payment_proofs/dummy_proof.png',
                'payment_submitted_at' => $startedAt->copy()->subHours(2),
                'approved_at' => $startedAt,
                'account_created_at' => $startedAt,
            ]);

            // Payment record (membership registration fee paid)
            \App\Models\Payment::query()->create([
                'invoice_number' => 'INV-' . $startedAt->format('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3))),
                'user_id' => $user->id,
                'payment_type' => 'membership',
                'amount' => $package->price,
                'payment_method' => $paymentMethod->type,
                'payment_status' => 'paid',
                'payment_date' => $startedAt,
            ]);

            return $user;
        };

        // 7. Generate 60 Members
        // 7.1. Seed default member@fitnez.test (Active, 12 Months Premium)
        $p12m = $packages->where('code', 'PKG_12_MONTHS_PREMIUM')->first();
        $createUserWithRegistration('member@fitnez.test', 'Fitnez Member', '080000000003', 'active', $p12m, $qrisMethod, $memberRole);

        // 7.2. Seed remaining 29 Active members
        for ($i = 1; $i <= 29; $i++) {
            $pkg = $packages->random();
            $pm = $paymentMethods->random();
            $createUserWithRegistration(
                "member.active.{$i}@fitnez.test",
                "Active Member {$i}",
                "081234567" . sprintf('%03d', $i),
                'active',
                $pkg,
                $pm,
                $memberRole
            );
        }

        // 7.3. Seed 15 Expiring Soon members (expires in 2 days)
        $p1m = $packages->where('code', 'PKG_1_MONTH')->first();
        for ($i = 1; $i <= 15; $i++) {
            $pm = $paymentMethods->random();
            $createUserWithRegistration(
                "member.expiring.{$i}@fitnez.test",
                "Expiring Member {$i}",
                "082234567" . sprintf('%03d', $i),
                'expiring',
                $p1m,
                $pm,
                $memberRole
            );
        }

        // 7.4. Seed 15 Expired members (expired 15 days ago)
        for ($i = 1; $i <= 15; $i++) {
            $pm = $paymentMethods->random();
            $createUserWithRegistration(
                "member.expired.{$i}@fitnez.test",
                "Expired Member {$i}",
                "083234567" . sprintf('%03d', $i),
                'expired',
                $p1m,
                $pm,
                $memberRole
            );
        }

        // 8. Generate 20 Trainers (Approved Applications)
        // 8.1. Seed default trainer@fitnez.test (Active, 6 Months Plus)
        $p6m = $packages->where('code', 'PKG_6_MONTHS_PLUS')->first();
        $defaultTrainer = $createUserWithRegistration(
            'trainer@fitnez.test',
            'Fitnez Approved Trainer',
            '080000000002',
            'active',
            $p6m,
            $bankMethod,
            $trainerRole
        );

        TrainerApplication::query()->create([
            'user_id' => $defaultTrainer->id,
            'status' => 'approved',
            'cv_document_url' => 'dummy/approved-trainer-cv.pdf',
            'certificate_document_url' => 'dummy/approved-trainer-certificate.pdf',
            'submitted_at' => $defaultTrainer->membership_started_at,
            'reviewed_at' => $defaultTrainer->membership_started_at->copy()->addDay(),
            'reviewed_by_admin_id' => $admin->id,
            'admin_notes' => 'Dummy trainer account approved by seeder.',
        ]);

        TrainerDetail::query()->create([
            'user_id' => $defaultTrainer->id,
            'specialization' => 'Strength Training',
            'biography' => 'Dummy approved trainer account for Fitnez testing.',
            'experience_years' => 3,
            'hourly_rate' => 150000,
            'avg_rating' => 4.8,
        ]);

        // 8.2. Seed remaining 19 Trainers
        for ($i = 1; $i <= 19; $i++) {
            $pkg = $packages->random();
            $pm = $paymentMethods->random();
            $trainerUser = $createUserWithRegistration(
                "trainer.active.{$i}@fitnez.test",
                "Trainer Coach {$i}",
                "084234567" . sprintf('%03d', $i),
                'active',
                $pkg,
                $pm,
                $trainerRole
            );

            TrainerApplication::query()->create([
                'user_id' => $trainerUser->id,
                'status' => 'approved',
                'cv_document_url' => "cvs/trainer_{$i}.pdf",
                'certificate_document_url' => "certificates/trainer_{$i}.pdf",
                'submitted_at' => $trainerUser->membership_started_at,
                'reviewed_at' => $trainerUser->membership_started_at->copy()->addDay(),
                'reviewed_by_admin_id' => $admin->id,
                'admin_notes' => 'Approved automatically by seeder.',
            ]);

            $specs = ['Strength Training', 'Weight Loss', 'Yoga', 'Cardio Fitness', 'Bodybuilding'];
            TrainerDetail::query()->create([
                'user_id' => $trainerUser->id,
                'specialization' => $specs[array_rand($specs)],
                'biography' => "Certified gym trainer with years of coaching experience.",
                'experience_years' => rand(2, 8),
                'hourly_rate' => rand(120, 250) * 1000,
                'avg_rating' => rand(43, 50) / 10.0,
            ]);
        }

        // 9. Generate 20 Members Ready to Become Trainers (Pending Trainer Applications)
        // 9.1. Seed 10 Active applicant members
        for ($i = 1; $i <= 10; $i++) {
            $pkg = $packages->random();
            $pm = $paymentMethods->random();
            $applicant = $createUserWithRegistration(
                "applicant.active.{$i}@fitnez.test",
                "Applicant Active {$i}",
                "085234567" . sprintf('%03d', $i),
                'active',
                $pkg,
                $pm,
                $memberRole
            );

            TrainerApplication::query()->create([
                'user_id' => $applicant->id,
                'status' => 'pending',
                'cv_document_url' => "cvs/applicant_active_{$i}.pdf",
                'certificate_document_url' => "certificates/applicant_active_{$i}.pdf",
                'submitted_at' => now()->subDays(rand(1, 4)),
            ]);
        }

        // 9.2. Seed 5 Expiring applicant members
        for ($i = 1; $i <= 5; $i++) {
            $pm = $paymentMethods->random();
            $applicant = $createUserWithRegistration(
                "applicant.expiring.{$i}@fitnez.test",
                "Applicant Expiring {$i}",
                "086234567" . sprintf('%03d', $i),
                'expiring',
                $p1m,
                $pm,
                $memberRole
            );

            TrainerApplication::query()->create([
                'user_id' => $applicant->id,
                'status' => 'pending',
                'cv_document_url' => "cvs/applicant_expiring_{$i}.pdf",
                'certificate_document_url' => "certificates/applicant_expiring_{$i}.pdf",
                'submitted_at' => now()->subDays(rand(1, 2)),
            ]);
        }

        // 9.3. Seed 5 Expired applicant members
        for ($i = 1; $i <= 5; $i++) {
            $pm = $paymentMethods->random();
            $applicant = $createUserWithRegistration(
                "applicant.expired.{$i}@fitnez.test",
                "Applicant Expired {$i}",
                "087234567" . sprintf('%03d', $i),
                'expired',
                $p1m,
                $pm,
                $memberRole
            );

            TrainerApplication::query()->create([
                'user_id' => $applicant->id,
                'status' => 'pending',
                'cv_document_url' => "cvs/applicant_expired_{$i}.pdf",
                'certificate_document_url' => "certificates/applicant_expired_{$i}.pdf",
                'submitted_at' => now()->subDays(rand(1, 5)),
            ]);
        }

        // 10. Generate Attendance History and Live Logs
        $allUsers = User::query()->where('role_id', '!=', $adminRole->id)->get();
        
        // 10.1. Generate historical check-ins over the last 15 days
        foreach ($allUsers as $user) {
            $entriesCount = rand(1, 6);
            for ($j = 0; $j < $entriesCount; $j++) {
                $daysAgo = rand(1, 15);
                $checkInTime = now()->subDays($daysAgo)->subHours(rand(1, 12))->subMinutes(rand(1, 59));
                $checkOutTime = $checkInTime->copy()->addMinutes(rand(45, 180));
                
                Attendance::query()->create([
                    'user_id' => $user->id,
                    'attendance_type' => $user->isTrainer() ? 'trainer_check_in' : 'member_check_in',
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                ]);
            }
        }

        // 10.2. Generate 8 entries for TODAY to make the live metrics functional
        for ($j = 1; $j <= 8; $j++) {
            $user = $allUsers->random();
            $checkInTime = now()->subHours(rand(1, 6));
            // Keep some active in-progress check-ins (check_out_time is null)
            $checkOutTime = ($j % 3 === 0) ? null : $checkInTime->copy()->addMinutes(rand(45, 120));

            Attendance::query()->create([
                'user_id' => $user->id,
                'attendance_type' => $user->isTrainer() ? 'trainer_check_in' : 'member_check_in',
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
            ]);
        }

        // 11. Seed landing page visits for visitor analytics
        DB::table('landing_page_visits')->delete();
        $countriesIPs = [
            '182.253.0.1', // ID
            '8.8.8.8', // US
            '202.130.96.1', // ID
            '111.95.0.1', // ID
            '45.32.0.1', // SG
            '118.189.0.1', // SG
            '210.140.0.1', // JP
            '195.154.0.1', // FR
            '82.165.0.1', // DE
            '1.1.1.1' // AU
        ];
        
        for ($i = 0; $i < 60; $i++) {
            $daysAgo = rand(0, 8);
            $visitedAt = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));
            $visitorUuid = \Illuminate\Support\Str::uuid()->toString();
            $sessionUuid = \Illuminate\Support\Str::uuid()->toString();
            
            DB::table('landing_page_visits')->insert([
                'visitor_uuid' => $visitorUuid,
                'session_uuid' => $sessionUuid,
                'visit_date' => $visitedAt->toDateString(),
                'ip_address' => $countriesIPs[array_rand($countriesIPs)],
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                'browser_name' => rand(0, 1) === 0 ? 'Chrome' : 'Safari',
                'os_name' => 'Windows',
                'device_type' => 'desktop',
                'route_path' => '/',
                'page_view_count' => rand(1, 5),
                'visited_at' => $visitedAt,
                'last_seen_at' => $visitedAt->copy()->addMinutes(rand(1, 10)),
                'created_at' => $visitedAt,
                'updated_at' => $visitedAt,
            ]);
        }
    }
}
