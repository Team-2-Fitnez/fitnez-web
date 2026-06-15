<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('attendance')) {
            Schema::create('attendance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('check_in_time')->nullable();
                $table->timestamp('check_out_time')->nullable();
                $table->string('attendance_type')->default('member_checkin');
                $table->foreignId('booking_id')->nullable()->constrained('trainer_bookings')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance');
    }
};
