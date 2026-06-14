<?php

namespace Database\Seeders;

use App\Features\Payments\Services\Membership\MembershipPackageFactory;
use App\Models\Attendance;
use App\Models\Faq;
use App\Models\FoodLog;
use App\Models\ManualPaymentMethod;
use App\Models\MealPlan;
use App\Models\MembershipPackage;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\ProspectiveMemberRegistration;
use App\Models\Role;
use App\Models\TrainerApplication;
use App\Models\TrainerBooking;
use App\Models\TrainerDetail;
use App\Models\TrainerEarning;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    private const PASSWORD = 'FitnezTeam2@2026';

    public function run(): void
    {
        DB::transaction(function () {
            $roles = $this->seedRoles();
            $packages = $this->seedMembershipPackages();
            $paymentMethods = $this->seedPaymentMethods();
            $admin = $this->seedAdmin($roles['admin']);

            $this->clearSeedData($admin->id);
            $this->seedFaqs();

            $members = $this->seedMembers($roles['member'], $packages, $paymentMethods, $admin);
            $trainers = $this->seedTrainers($roles['trainer'], $packages, $paymentMethods, $admin);

            $this->seedOperationalData($members, $trainers, $packages, $paymentMethods, $admin);
        });
    }

    private function seedRoles(): array
    {
        return [
            'admin' => Role::query()->firstOrCreate(['name' => 'admin'], ['description' => 'Fitnez administrator']),
            'member' => Role::query()->firstOrCreate(['name' => 'member'], ['description' => 'Fitnez member/user']),
            'trainer' => Role::query()->firstOrCreate(['name' => 'trainer'], ['description' => 'Fitnez personal trainer']),
        ];
    }

    private function seedAdmin(Role $adminRole): User
    {
        return User::query()->updateOrCreate(
            ['email' => 'admin@fitnez.test'],
            [
                'full_name' => 'Fitnez Admin',
                'phone' => '080000000001',
                'role_id' => $adminRole->id,
                'password_hash' => Hash::make(self::PASSWORD),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );
    }

    private function seedMembershipPackages()
    {
        foreach (MembershipPackageFactory::defaultPackages() as $package) {
            MembershipPackage::query()->updateOrCreate(
                ['code' => $package['code']],
                [
                    'name' => $package['name'],
                    'duration_months' => $package['duration_months'],
                    'price' => $package['price'],
                    'free_class_access' => $package['free_class_access'],
                    'benefits' => $package['benefits'],
                    'is_active' => true,
                ]
            );
        }

        return MembershipPackage::query()->where('is_active', true)->orderBy('duration_months')->get()->values();
    }

    private function seedPaymentMethods()
    {
        ManualPaymentMethod::query()->updateOrCreate(['code' => 'QRIS_FITNEZ'], [
            'type' => 'qris',
            'display_name' => 'QRIS Fitnez',
            'account_name' => 'FITNEZ GYM',
            'qris_image_url' => '/images/payment/qris-fitnez-placeholder.svg',
            'instructions' => 'Scan QRIS, input the package price manually, then upload proof.',
            'is_active' => true,
        ]);

        ManualPaymentMethod::query()->updateOrCreate(['code' => 'BANK_TRANSFER_BCA'], [
            'type' => 'bank_transfer',
            'display_name' => 'Bank Transfer BCA',
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'FITNEZ GYM',
            'instructions' => 'Transfer the package price to this account, then upload proof.',
            'is_active' => true,
        ]);

        return ManualPaymentMethod::query()->where('is_active', true)->orderBy('id')->get()->values();
    }

    private function clearSeedData(int $adminId): void
    {
        foreach ([
            'chat_messages', 'food_logs', 'class_members', 'classes', 'attendance', 'trainer_earnings',
            'payments', 'trainer_bookings', 'trainer_details', 'trainer_applications', 'workout_trackings',
            'workout_exercises', 'workout_plans', 'meal_plans', 'meals', 'nutrition_calculator',
            'prospective_member_registrations', 'notifications', 'user_devices', 'browser_tabs',
            'jwt_sessions', 'otp_codes', 'landing_page_visits',
        ] as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->delete();
            }
        }

        User::query()->where('id', '!=', $adminId)->delete();
    }

    private function seedFaqs(): void
    {
        $faqs = [
            ['How do I register as a Fitnez Gym member?', 'Register through the website, choose a package, upload payment proof, and wait for admin verification.', 'General', 1],
            ['What payment methods are supported?', 'Fitnez supports QRIS and BCA bank transfer for manual verification.', 'Payment', 2],
            ['How do I hire a Personal Trainer?', 'Log in as an active member, open Hire Trainer, choose a trainer, then upload payment proof.', 'Trainer', 3],
            ['Can I track meal and workout data?', 'Yes. Use the Mealplan, Nutrition Calculator, Nutrition Monitoring, and Workoutplan features.', 'Feature', 4],
        ];

        foreach ($faqs as [$question, $answer, $category, $sortOrder]) {
            Faq::query()->updateOrCreate(['question' => $question], [
                'answer' => $answer,
                'category' => $category,
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
        }
    }

    private function seedMembers(Role $memberRole, $packages, $paymentMethods, User $admin)
    {
        $members = collect();
        $hash = Hash::make(self::PASSWORD);

        for ($i = 1; $i <= 750; $i++) {
            $package = $packages[($i - 1) % $packages->count()];
            $paymentMethod = $paymentMethods[($i - 1) % $paymentMethods->count()];
            $statusType = $i % 15 === 0 ? 'expired' : ($i % 10 === 0 ? 'expiring' : 'active');
            $email = $i === 1 ? 'member@fitnez.test' : sprintf('member.%03d@fitnez.test', $i);
            $user = $this->createSeedUser($email, sprintf('Member Seed %03d', $i), sprintf('0812000%04d', $i), $memberRole, $package, $statusType, $hash);

            $this->createRegistrationAndPayment($user, $package, $paymentMethod, $admin, $hash, $i, 'member');
            $members->push($user);
        }

        return $members;
    }

    private function seedTrainers(Role $trainerRole, $packages, $paymentMethods, User $admin)
    {
        $trainers = collect();
        $hash = Hash::make(self::PASSWORD);
        $specializations = ['Strength Training', 'Weight Loss', 'Yoga', 'Cardio Fitness', 'Bodybuilding', 'Functional Training'];

        for ($i = 1; $i <= 250; $i++) {
            $package = $packages[($i - 1) % $packages->count()];
            $paymentMethod = $paymentMethods[($i - 1) % $paymentMethods->count()];
            $email = $i === 1 ? 'trainer@fitnez.test' : sprintf('trainer.%03d@fitnez.test', $i);
            $user = $this->createSeedUser($email, sprintf('Trainer Seed %03d', $i), sprintf('0822000%04d', $i), $trainerRole, $package, 'active', $hash);

            $this->createRegistrationAndPayment($user, $package, $paymentMethod, $admin, $hash, $i, 'trainer');

            TrainerApplication::query()->create([
                'user_id' => $user->id,
                'status' => 'approved',
                'cv_document_url' => sprintf('seed/cv/trainer_%03d.pdf', $i),
                'certificate_document_url' => sprintf('seed/certificates/trainer_%03d.pdf', $i),
                'submitted_at' => $user->membership_started_at,
                'reviewed_at' => $user->membership_started_at?->copy()->addDay(),
                'reviewed_by_admin_id' => $admin->id,
                'admin_notes' => 'Approved automatically by database seeder.',
            ]);

            TrainerDetail::query()->create([
                'user_id' => $user->id,
                'specialization' => $specializations[($i - 1) % count($specializations)],
                'biography' => 'Certified Fitnez trainer account generated for load testing and demo data.',
                'experience_years' => 1 + ($i % 10),
                'hourly_rate' => 100000 + (($i % 10) * 15000),
                'base_price' => 100000 + (($i % 10) * 15000),
                'avg_rating' => 4 + (($i % 10) / 10),
            ]);

            $trainers->push($user);
        }

        return $trainers;
    }

    private function createSeedUser(string $email, string $name, string $phone, Role $role, MembershipPackage $package, string $statusType, string $hash): User
    {
        [$startedAt, $expiresAt] = $this->membershipDates($package, $statusType);

        return User::query()->create([
            'full_name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role_id' => $role->id,
            'password_hash' => $hash,
            'is_active' => $statusType !== 'expired',
            'email_verified_at' => $startedAt,
            'membership_package_id' => $package->id,
            'membership_started_at' => $startedAt,
            'membership_expires_at' => $expiresAt,
            'free_class_access' => (bool) $package->free_class_access,
            'created_at' => $startedAt,
            'updated_at' => now(),
        ]);
    }

    private function membershipDates(MembershipPackage $package, string $statusType): array
    {
        $now = now();

        if ($statusType === 'expired') {
            $startedAt = $now->copy()->subMonths(max(1, $package->duration_months))->subDays(10);
            return [$startedAt, $now->copy()->subDays(10)];
        }

        if ($statusType === 'expiring') {
            $expiresAt = $now->copy()->addDays(3);
            return [$expiresAt->copy()->subMonths(max(1, $package->duration_months)), $expiresAt];
        }

        $startedAt = $now->copy()->subDays(random_int(1, 25));
        return [$startedAt, $startedAt->copy()->addMonths($package->duration_months)];
    }

    private function createRegistrationAndPayment(User $user, MembershipPackage $package, ManualPaymentMethod $paymentMethod, User $admin, string $hash, int $sequence, string $prefix): void
    {
        ProspectiveMemberRegistration::query()->create([
            'membership_package_id' => $package->id,
            'manual_payment_method_id' => $paymentMethod->id,
            'user_id' => $user->id,
            'admin_id' => $admin->id,
            'registration_code' => sprintf('REG-%s-%04d', strtoupper($prefix), $sequence),
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'password_hash' => $hash,
            'amount' => $package->price,
            'status' => 'approved',
            'payment_proof_path' => 'seed/payment_proofs/dummy_proof.png',
            'payment_submitted_at' => $user->membership_started_at?->copy()->subHours(2),
            'approved_at' => $user->membership_started_at,
            'account_created_at' => $user->membership_started_at,
        ]);

        Payment::query()->create([
            'invoice_number' => sprintf('INV-%s-%04d', strtoupper($prefix), $sequence),
            'user_id' => $user->id,
            'membership_package_id' => $package->id,
            'payment_type' => 'membership',
            'amount' => $package->price,
            'payment_method' => $paymentMethod->type,
            'payment_status' => 'paid',
            'payment_date' => $user->membership_started_at,
            'external_reference' => 'SEED-' . Str::upper(Str::random(10)),
        ]);
    }

    private function seedOperationalData($members, $trainers, $packages, $paymentMethods, User $admin): void
    {
        $now = now();

        foreach ($members->take(250) as $index => $member) {
            MealPlan::query()->create([
                'user_id' => $member->id,
                'daily_limit' => 2000 + ($index % 400),
                'bmr' => 1500 + ($index % 300),
                'tdee' => 2100 + ($index % 500),
                'target_kal' => 1900 + ($index % 450),
            ]);

            FoodLog::query()->create([
                'user_id' => $member->id,
                'food_name' => 'Seed Meal ' . ($index + 1),
                'calories' => 300 + ($index % 500),
                'logged_date' => $now->toDateString(),
            ]);
        }

        foreach ($members->take(200) as $index => $member) {
            Attendance::query()->create([
                'user_id' => $member->id,
                'attendance_type' => 'member_check_in',
                'check_in_time' => $now->copy()->subDays($index % 30)->setTime(8 + ($index % 8), 0),
                'check_out_time' => $now->copy()->subDays($index % 30)->setTime(10 + ($index % 8), 0),
            ]);
        }

        foreach ($trainers->take(100) as $index => $trainer) {
            Attendance::query()->create([
                'user_id' => $trainer->id,
                'attendance_type' => 'trainer_check_in',
                'check_in_time' => $now->copy()->subDays($index % 30)->setTime(7 + ($index % 8), 30),
                'check_out_time' => $now->copy()->subDays($index % 30)->setTime(11 + ($index % 8), 30),
            ]);
        }

        foreach ($members->take(80) as $index => $member) {
            $trainer = $trainers[$index % $trainers->count()];
            $start = $now->copy()->addDays(($index % 20) + 1)->toDateString();
            $end = $now->copy()->addDays(($index % 20) + 28)->toDateString();

            $booking = TrainerBooking::query()->create([
                'member_id' => $member->id,
                'trainer_id' => $trainer->id,
                'start_date' => $start,
                'end_date' => $end,
                'sessions_per_week' => 3,
                'session_days' => ['monday', 'wednesday', 'friday'],
                'session_time' => sprintf('%02d:00', 8 + ($index % 8)),
                'member_notes' => 'Seed booking data for admin review.',
                'base_price_per_session' => 100000,
                'member_price_per_session' => 150000,
                'total_member_price' => 1800000,
                'total_trainer_price' => 1200000,
                'total_sessions' => 12,
                'status' => $index % 3 === 0 ? TrainerBooking::STATUS_PENDING_PAYMENT : TrainerBooking::STATUS_CONFIRMED,
                'payment_proof_path' => $index % 3 === 0 ? 'seed/payment_proofs/booking.png' : null,
                'paid_at' => $index % 3 === 0 ? null : $now,
            ]);

            if ($booking->status === TrainerBooking::STATUS_CONFIRMED) {
                TrainerEarning::query()->create([
                    'trainer_id' => $trainer->id,
                    'booking_id' => $booking->id,
                    'trainer_amount' => $booking->total_trainer_price,
                    'status' => 'pending',
                ]);
            }
        }

        foreach ($members->take(120) as $index => $member) {
            Notification::query()->create([
                'user_id' => $member->id,
                'title' => 'Welcome to Fitnez',
                'body' => 'Your seed account is ready for testing.',
                'notification_type' => 'system',
                'is_read' => $index % 2 === 0,
            ]);
        }
    }
}
