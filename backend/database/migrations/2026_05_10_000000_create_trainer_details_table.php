<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('trainer_details')) {
            return;
        }

        Schema::create('trainer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('specialization')->nullable()->index();
            $table->text('biography')->nullable();
            $table->unsignedTinyInteger('experience_years')->default(0);
            $table->decimal('hourly_rate', 12, 2)->default(0);
            $table->decimal('base_price', 12, 2)->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['avg_rating', 'experience_years']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainer_details');
    }
};
