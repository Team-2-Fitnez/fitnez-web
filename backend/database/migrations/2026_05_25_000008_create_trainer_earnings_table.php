<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainer_earnings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('booking_id')->nullable()->constrained('trainer_bookings')->onDelete('set null');
            $table->decimal('commission_rate', 5, 2)->default(0);
            $table->decimal('trainer_amount', 15, 2)->default(0);
            $table->string('status')->default('pending');
            $table->timestamp('disbursed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_earnings');
    }
};
