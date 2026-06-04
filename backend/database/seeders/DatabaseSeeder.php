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

        $member = User::query()->updateOrCreate(
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

        // Seed some sample notifications for testing
        Notification::query()->create([
            'user_id' => $trainerUser->id,
            'title' => 'Permintaan sesi baru',
            'body' => 'Fitnez Member memesan sesi pada 01 Jun 2026 pukul 09:00–10:00. Tinjau di Jadwal Melatih.',
            'notification_type' => 'booking_request',
            'is_read' => false,
        ]);

        Notification::query()->create([
            'user_id' => $trainerUser->id,
            'title' => 'Sesi dikonfirmasi & pembayaran',
            'body' => 'Sesi dengan Fitnez Member pada 31 Mei 2026 telah dikonfirmasi. Total pembayaran Rp 150.000.',
            'notification_type' => 'payment_in',
            'is_read' => true,
        ]);

        Notification::query()->create([
            'user_id' => $member->id,
            'title' => 'Selamat datang di Fitnez!',
            'body' => 'Terima kasih telah bergabung. Mulai perjalanan fitness Anda sekarang.',
            'notification_type' => 'welcome',
            'is_read' => false,
        ]);

        Notification::query()->create([
            'user_id' => $admin->id,
            'title' => 'Pendaftaran Trainer Baru',
            'body' => 'Ada aplikasi trainer baru yang perlu direview.',
            'notification_type' => 'trainer_application',
            'is_read' => false,
        ]);

        // Sample bookings for testing
        $sampleBooking = TrainerBooking::query()->updateOrCreate(
            ['member_id' => $member->id, 'trainer_id' => $trainerUser->id, 'booking_date' => now()->addDays(1)->toDateString()],
            [
                'start_time' => '09:00',
                'end_time' => '10:00',
                'session_type' => 'online',
                'location' => 'Zoom',
                'status' => 'confirmed',
                'total_price' => 150000,
            ]
        );

        $pastBooking = TrainerBooking::query()->updateOrCreate(
            ['member_id' => $member->id, 'trainer_id' => $trainerUser->id, 'booking_date' => now()->subDays(2)->toDateString()],
            [
                'start_time' => '14:00',
                'end_time' => '15:00',
                'session_type' => 'offline',
                'location' => 'Fitnez Gym',
                'status' => 'completed',
                'total_price' => 150000,
            ]
        );

        // Sample attendance for trainer
        Attendance::query()->create([
            'user_id' => $trainerUser->id,
            'check_in_time' => now()->subDays(1)->setHour(8)->setMinute(0),
            'check_out_time' => now()->subDays(1)->setHour(17)->setMinute(0),
            'attendance_type' => 'trainer_checkin',
        ]);

        Attendance::query()->create([
            'user_id' => $trainerUser->id,
            'check_in_time' => now()->subDays(2)->setHour(8)->setMinute(15),
            'check_out_time' => now()->subDays(2)->setHour(16)->setMinute(45),
            'attendance_type' => 'trainer_checkin',
        ]);

        // Sample attendance for member
        Attendance::query()->create([
            'user_id' => $member->id,
            'check_in_time' => now()->subDays(1)->setHour(7)->setMinute(30),
            'check_out_time' => now()->subDays(1)->setHour(9)->setMinute(0),
            'attendance_type' => 'member_checkin',
        ]);

        // Sample payment for completed booking
        $samplePayment = Payment::query()->updateOrCreate(
            ['invoice_number' => 'INV-2026-TEST-001'],
            [
                'user_id' => $member->id,
                'booking_id' => $pastBooking->id,
                'payment_type' => 'booking',
                'amount' => 150000,
                'payment_method' => 'transfer',
                'payment_status' => 'paid',
                'payment_date' => now()->subDays(2),
            ]
        );

        // Sample trainer earning
        TrainerEarning::query()->updateOrCreate(
            ['trainer_id' => $trainerUser->id, 'payment_id' => $samplePayment->id],
            [
                'booking_id' => $pastBooking->id,
                'commission_rate' => 80.00,
                'trainer_amount' => 120000,
                'status' => 'paid',
                'disbursed_at' => now()->subDays(1),
            ]
        );
    }
}
