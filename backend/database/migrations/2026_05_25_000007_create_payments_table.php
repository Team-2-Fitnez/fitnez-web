<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->foreignId('booking_id')->nullable()->constrained('trainer_bookings')->onDelete('set null');
                $table->string('payment_type');
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('payment_method')->nullable();
                $table->string('payment_status')->default('pending');
                $table->timestamp('payment_date')->nullable();
                $table->string('external_reference')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
