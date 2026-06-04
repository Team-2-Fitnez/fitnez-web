<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exercises')) {
            Schema::create('exercises', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('muscle_group')->nullable();
                $table->string('equipment')->nullable();
                $table->string('tutorial_video_url')->nullable();
                $table->string('tutorial_image_url')->nullable();
                $table->text('instructions')->nullable();
            });
        }

        if (! Schema::hasTable('workout_exercises')) {
            Schema::create('workout_exercises', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workout_plan_id')->constrained('workout_plans')->cascadeOnDelete();
                $table->foreignId('exercise_id')->nullable()->constrained('exercises')->nullOnDelete();
                $table->unsignedTinyInteger('day_of_week')->nullable();
                $table->unsignedInteger('sets')->nullable();
                $table->unsignedInteger('reps')->nullable();
                $table->unsignedInteger('rest_seconds')->nullable();
                $table->text('notes')->nullable();
            });
        }

        if (! Schema::hasTable('workout_trackings')) {
            Schema::create('workout_trackings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('workout_plan_id')->nullable()->constrained('workout_plans')->nullOnDelete();
                $table->foreignId('workout_exercise_id')->nullable()->constrained('workout_exercises')->nullOnDelete();
                $table->string('exercise_name')->nullable();
                $table->date('workout_date')->nullable();
                $table->unsignedInteger('actual_sets')->nullable();
                $table->unsignedInteger('actual_reps')->nullable();
                $table->decimal('actual_weight_kg', 8, 2)->nullable();
                $table->unsignedInteger('sets')->nullable();
                $table->unsignedInteger('reps')->nullable();
                $table->decimal('weight', 8, 2)->nullable();
                $table->unsignedInteger('duration_minutes')->nullable();
                $table->date('tracked_at')->nullable();
                $table->boolean('is_completed')->default(false);
                $table->boolean('completed')->default(false);
                $table->text('notes')->nullable();
                $table->timestamp('logged_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('nutrition_calculator')) {
            Schema::create('nutrition_calculator', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->unsignedInteger('age')->nullable();
                $table->string('gender')->nullable();
                $table->decimal('height_cm', 6, 2)->nullable();
                $table->decimal('weight_kg', 6, 2)->nullable();
                $table->string('activity_level')->nullable();
                $table->string('goal')->nullable();
                $table->decimal('bmr', 10, 2)->nullable();
                $table->decimal('tdee', 10, 2)->nullable();
                $table->decimal('target_calories', 10, 2)->nullable();
                $table->decimal('target_protein', 10, 2)->nullable();
                $table->decimal('target_carbs', 10, 2)->nullable();
                $table->decimal('target_fat', 10, 2)->nullable();
                $table->timestamp('calculated_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('meals')) {
            Schema::create('meals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('meal_plan_id')->constrained('meal_plans')->cascadeOnDelete();
                $table->string('meal_type')->nullable();
                $table->string('food_name');
                $table->decimal('portion_grams', 8, 2)->nullable();
                $table->decimal('calories', 10, 2)->nullable();
                $table->decimal('protein', 10, 2)->nullable();
                $table->decimal('carbs', 10, 2)->nullable();
                $table->decimal('fat', 10, 2)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('meals');
        Schema::dropIfExists('nutrition_calculator');
        Schema::dropIfExists('workout_trackings');
        Schema::dropIfExists('workout_exercises');
        Schema::dropIfExists('exercises');
    }
};
