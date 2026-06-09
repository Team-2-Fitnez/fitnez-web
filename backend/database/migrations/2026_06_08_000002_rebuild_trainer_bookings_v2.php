<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('trainer_bookings', 'start_date')) {
            return;
        }

        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->dropColumn(['booking_date', 'start_time', 'end_time', 'session_type', 'location', 'total_price']);

            $table->date('start_date')->after('trainer_id');
            $table->date('end_date')->after('start_date');
            $table->tinyInteger('sessions_per_week')->after('end_date');
            $table->json('session_days')->after('sessions_per_week');
            $table->string('session_time', 5)->after('session_days');
            $table->decimal('base_price_per_session', 12, 2)->default(0)->after('member_notes');
            $table->decimal('member_price_per_session', 12, 2)->default(0)->after('base_price_per_session');
            $table->decimal('total_member_price', 12, 2)->default(0)->after('member_price_per_session');
            $table->decimal('total_trainer_price', 12, 2)->default(0)->after('total_member_price');
            $table->integer('total_sessions')->default(0)->after('total_trainer_price');
            $table->text('payment_proof_path')->nullable()->after('total_sessions');
            $table->timestamp('paid_at')->nullable()->after('payment_proof_path');
        });

        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->string('status', 30)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->dropColumn([
                'start_date', 'end_date', 'sessions_per_week',
                'session_days', 'session_time',
                'base_price_per_session', 'member_price_per_session',
                'total_member_price', 'total_trainer_price', 'total_sessions',
                'payment_proof_path', 'paid_at',
            ]);

            $table->date('booking_date')->after('trainer_id');
            $table->string('start_time', 5)->after('booking_date');
            $table->string('end_time', 5)->after('start_time');
            $table->string('session_type', 20)->default('online');
            $table->string('location')->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
        });

        Schema::table('trainer_bookings', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
