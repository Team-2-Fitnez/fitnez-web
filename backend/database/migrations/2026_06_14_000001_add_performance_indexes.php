<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        ['users', 'fitnez_users_role_active_idx', 'CREATE INDEX IF NOT EXISTS fitnez_users_role_active_idx ON users (role_id, is_active, id DESC)'],
        ['users', 'fitnez_users_email_lower_idx', 'CREATE INDEX IF NOT EXISTS fitnez_users_email_lower_idx ON users (LOWER(email))'],
        ['users', 'fitnez_users_full_name_lower_idx', 'CREATE INDEX IF NOT EXISTS fitnez_users_full_name_lower_idx ON users (LOWER(full_name))'],
        ['users', 'fitnez_users_phone_idx', 'CREATE INDEX IF NOT EXISTS fitnez_users_phone_idx ON users (phone)'],
        ['trainer_details', 'fitnez_trainer_details_user_idx', 'CREATE INDEX IF NOT EXISTS fitnez_trainer_details_user_idx ON trainer_details (user_id)'],
        ['trainer_details', 'fitnez_trainer_details_rating_idx', 'CREATE INDEX IF NOT EXISTS fitnez_trainer_details_rating_idx ON trainer_details (avg_rating DESC, id DESC)'],
        ['trainer_bookings', 'fitnez_bookings_member_status_idx', 'CREATE INDEX IF NOT EXISTS fitnez_bookings_member_status_idx ON trainer_bookings (member_id, status, start_date DESC)'],
        ['trainer_bookings', 'fitnez_bookings_trainer_status_idx', 'CREATE INDEX IF NOT EXISTS fitnez_bookings_trainer_status_idx ON trainer_bookings (trainer_id, status, start_date DESC)'],
        ['trainer_bookings', 'fitnez_bookings_status_created_idx', 'CREATE INDEX IF NOT EXISTS fitnez_bookings_status_created_idx ON trainer_bookings (status, created_at DESC)'],
        ['chat_messages', 'fitnez_chat_pair_id_idx', 'CREATE INDEX IF NOT EXISTS fitnez_chat_pair_id_idx ON chat_messages (sender_id, receiver_id, id DESC)'],
        ['chat_messages', 'fitnez_chat_receiver_read_idx', 'CREATE INDEX IF NOT EXISTS fitnez_chat_receiver_read_idx ON chat_messages (receiver_id, is_read, id DESC)'],
        ['notifications', 'fitnez_notifications_user_read_idx', 'CREATE INDEX IF NOT EXISTS fitnez_notifications_user_read_idx ON notifications (user_id, is_read, id DESC)'],
        ['notifications', 'fitnez_notifications_type_idx', 'CREATE INDEX IF NOT EXISTS fitnez_notifications_type_idx ON notifications (notification_type, id DESC)'],
        ['payments', 'fitnez_payments_user_status_idx', 'CREATE INDEX IF NOT EXISTS fitnez_payments_user_status_idx ON payments (user_id, payment_status, id DESC)'],
        ['payments', 'fitnez_payments_type_status_idx', 'CREATE INDEX IF NOT EXISTS fitnez_payments_type_status_idx ON payments (payment_type, payment_status, id DESC)'],
        ['attendance', 'fitnez_attendance_user_time_idx', 'CREATE INDEX IF NOT EXISTS fitnez_attendance_user_time_idx ON attendance (user_id, check_in_time DESC)'],
        ['food_logs', 'fitnez_food_logs_user_date_idx', 'CREATE INDEX IF NOT EXISTS fitnez_food_logs_user_date_idx ON food_logs (user_id, logged_date, id DESC)'],
        ['workout_plans', 'fitnez_workout_user_date_idx', 'CREATE INDEX IF NOT EXISTS fitnez_workout_user_date_idx ON workout_plans (user_id, date, id DESC)'],
        ['prospective_member_registrations', 'fitnez_registrations_status_created_idx', 'CREATE INDEX IF NOT EXISTS fitnez_registrations_status_created_idx ON prospective_member_registrations (status, created_at DESC)'],
        ['trainer_applications', 'fitnez_trainer_applications_status_idx', 'CREATE INDEX IF NOT EXISTS fitnez_trainer_applications_status_idx ON trainer_applications (status, submitted_at DESC)'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as [$table, , $sql]) {
            if (Schema::hasTable($table)) {
                DB::statement($sql);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [, $name]) {
            DB::statement("DROP INDEX IF EXISTS {$name}");
        }
    }
};
