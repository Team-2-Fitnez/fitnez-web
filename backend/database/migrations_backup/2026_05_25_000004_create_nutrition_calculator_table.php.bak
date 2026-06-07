<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_calculator', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('bmr', 10, 2)->default(0);
            $table->decimal('tdee', 10, 2)->default(0);
            $table->decimal('target_calories', 10, 2)->default(0);
            $table->decimal('target_protein', 10, 2)->default(0);
            $table->decimal('target_carbs', 10, 2)->default(0);
            $table->decimal('target_fat', 10, 2)->default(0);
            $table->timestamp('calculated_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_calculator');
    }
};
