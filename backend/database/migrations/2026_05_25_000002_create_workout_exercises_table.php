<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workout_exercises')) {
            Schema::create('workout_exercises', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('workout_plan_id');
                $table->unsignedBigInteger('exercise_id');
                $table->integer('day_of_week')->nullable();
                $table->integer('sets')->default(0);
                $table->integer('reps')->default(0);
                $table->integer('rest_seconds')->nullable();
                $table->text('notes')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });

            return;
        }

        Schema::table('workout_exercises', function (Blueprint $table) {
            if (! Schema::hasColumn('workout_exercises', 'workout_plan_id')) {
                $table->unsignedBigInteger('workout_plan_id');
            }

            if (! Schema::hasColumn('workout_exercises', 'exercise_id')) {
                $table->unsignedBigInteger('exercise_id');
            }

            if (! Schema::hasColumn('workout_exercises', 'day_of_week')) {
                $table->integer('day_of_week')->nullable();
            }

            if (! Schema::hasColumn('workout_exercises', 'sets')) {
                $table->integer('sets')->default(0);
            }

            if (! Schema::hasColumn('workout_exercises', 'reps')) {
                $table->integer('reps')->default(0);
            }

            if (! Schema::hasColumn('workout_exercises', 'rest_seconds')) {
                $table->integer('rest_seconds')->nullable();
            }

            if (! Schema::hasColumn('workout_exercises', 'notes')) {
                $table->text('notes')->nullable();
            }

            if (! Schema::hasColumn('workout_exercises', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('workout_exercises', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // This table may be created by another migration, so do not drop it here.
    }
};
