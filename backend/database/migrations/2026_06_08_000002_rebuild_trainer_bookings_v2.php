<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trainer_bookings')) {
            return;
        }

        Schema::table('trainer_bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('trainer_bookings', 'start_date')) {
                $table->date('start_date')->nullable()->after('trainer_id')->index();
            }
            if (! Schema::hasColumn('trainer_bookings', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date')->index();
            }
            if (! Schema::hasColumn('trainer_bookings', 'sessions_per_week')) {
                $table->tinyInteger('sessions_per_week')->default(3)->after('end_date');
            }
            if (! Schema::hasColumn('trainer_bookings', 'session_days')) {
                $table->json('session_days')->nullable()->after('sessions_per_week');
            }
            if (! Schema::hasColumn('trainer_bookings', 'session_time')) {
                $table->string('session_time', 5)->nullable()->after('session_days')->index();
            }
            if (! Schema::hasColumn('trainer_bookings', 'base_price_per_session')) {
                $table->decimal('base_price_per_session', 12, 2)->default(0)->after('member_notes');
            }
            if (! Schema::hasColumn('trainer_bookings', 'member_price_per_session')) {
                $table->decimal('member_price_per_session', 12, 2)->default(0)->after('base_price_per_session');
            }
            if (! Schema::hasColumn('trainer_bookings', 'total_member_price')) {
                $table->decimal('total_member_price', 12, 2)->default(0)->after('member_price_per_session');
            }
            if (! Schema::hasColumn('trainer_bookings', 'total_trainer_price')) {
                $table->decimal('total_trainer_price', 12, 2)->default(0)->after('total_member_price');
            }
            if (! Schema::hasColumn('trainer_bookings', 'total_sessions')) {
                $table->integer('total_sessions')->default(0)->after('total_trainer_price');
            }
            if (! Schema::hasColumn('trainer_bookings', 'payment_proof_path')) {
                $table->text('payment_proof_path')->nullable()->after('total_sessions');
            }
            if (! Schema::hasColumn('trainer_bookings', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_proof_path');
            }
        });

        foreach (['booking_date', 'start_time', 'end_time', 'session_type', 'location', 'total_price'] as $legacyColumn) {
            if (Schema::hasColumn('trainer_bookings', $legacyColumn)) {
                Schema::table('trainer_bookings', function (Blueprint $table) use ($legacyColumn) {
                    $table->dropColumn($legacyColumn);
                });
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('trainer_bookings')) {
            return;
        }

        foreach ([
            'start_date', 'end_date', 'sessions_per_week', 'session_days', 'session_time',
            'base_price_per_session', 'member_price_per_session', 'total_member_price',
            'total_trainer_price', 'total_sessions', 'payment_proof_path', 'paid_at',
        ] as $column) {
            if (Schema::hasColumn('trainer_bookings', $column)) {
                Schema::table('trainer_bookings', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
