<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('workout_trackings')) {
            Schema::create('workout_trackings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('workout_exercise_id')->nullable();
                $table->date('workout_date');
                $table->integer('actual_sets')->default(0);
                $table->integer('actual_reps')->default(0);
                $table->integer('actual_weight_kg')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->timestamp('logged_at')->useCurrent();
            });

            return;
        }

        Schema::table('workout_trackings', function (Blueprint $table) {
            if (! Schema::hasColumn('workout_trackings', 'user_id')) {
                $table->unsignedBigInteger('user_id');
            }

            if (! Schema::hasColumn('workout_trackings', 'workout_exercise_id')) {
                $table->unsignedBigInteger('workout_exercise_id')->nullable();
            }

            if (! Schema::hasColumn('workout_trackings', 'workout_date')) {
                $table->date('workout_date');
            }

            if (! Schema::hasColumn('workout_trackings', 'actual_sets')) {
                $table->integer('actual_sets')->default(0);
            }

            if (! Schema::hasColumn('workout_trackings', 'actual_reps')) {
                $table->integer('actual_reps')->default(0);
            }

            if (! Schema::hasColumn('workout_trackings', 'actual_weight_kg')) {
                $table->integer('actual_weight_kg')->default(0);
            }

            if (! Schema::hasColumn('workout_trackings', 'is_completed')) {
                $table->boolean('is_completed')->default(false);
            }

            if (! Schema::hasColumn('workout_trackings', 'logged_at')) {
                $table->timestamp('logged_at')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        // This table may be created by another migration, so do not drop it here.
    }
};
