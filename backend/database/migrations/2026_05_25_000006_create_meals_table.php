<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meal_plan_id')->constrained()->onDelete('cascade');
            $table->string('meal_type');
            $table->string('food_name');
            $table->integer('portion_grams')->default(0);
            $table->decimal('calories', 10, 2)->default(0);
            $table->decimal('protein', 10, 2)->default(0);
            $table->decimal('carbs', 10, 2)->default(0);
            $table->decimal('fat', 10, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
    }
};
