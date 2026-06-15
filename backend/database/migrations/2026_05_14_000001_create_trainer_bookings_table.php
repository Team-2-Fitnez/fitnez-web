<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trainer_bookings')) {
            return;
        }

        Schema::create('trainer_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trainer_id')->constrained('users')->cascadeOnDelete();
            $table->date('booking_date');
            $table->string('start_time', 5);
            $table->string('end_time', 5);
            $table->string('session_type', 20)->default('online');
            $table->string('location')->nullable();
            $table->text('member_notes')->nullable();
            $table->string('status', 20)->default('pending');
            $table->decimal('total_price', 12, 2)->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_bookings');
    }
};
