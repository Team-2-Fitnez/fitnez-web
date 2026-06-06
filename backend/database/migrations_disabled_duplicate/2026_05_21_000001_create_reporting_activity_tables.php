<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_logs')) {
            Schema::create('system_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action_type', 80)->index();
                $table->string('table_affected')->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->nullable()->unique();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('membership_package_id')->nullable()->constrained('membership_packages')->nullOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained('trainer_bookings')->nullOnDelete();
                $table->string('payment_type')->nullable();
                $table->string('type')->nullable();
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('payment_status')->default('pending')->index();
                $table->string('status')->nullable()->index();
                $table->timestamp('payment_date')->nullable();
                $table->timestamp('paid_at')->nullable();
                $table->string('external_reference')->nullable();
                $table->string('proof_path')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('attendance')) {
            Schema::create('attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->timestamp('check_in_time')->nullable();
                $table->timestamp('check_out_time')->nullable();
                $table->date('attendance_date')->nullable();
                $table->string('attendance_type')->default('gym');
                $table->string('status')->default('checked_in')->index();
                $table->text('notes')->nullable();
                $table->foreignId('booking_id')->nullable()->constrained('trainer_bookings')->nullOnDelete();
                $table->timestamps();
                $table->index(['user_id', 'check_in_time']);
            });
        }

        if (! Schema::hasTable('trainer_earnings')) {
            Schema::create('trainer_earnings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('member_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
                $table->foreignId('booking_id')->nullable()->constrained('trainer_bookings')->nullOnDelete();
                $table->decimal('commission_rate', 5, 2)->default(100);
                $table->decimal('trainer_amount', 12, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);
                $table->string('status')->default('pending')->index();
                $table->date('earned_at')->nullable();
                $table->date('paid_at')->nullable();
                $table->timestamp('disbursed_at')->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_earnings');
        Schema::dropIfExists('attendance');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('system_logs');
    }
};
