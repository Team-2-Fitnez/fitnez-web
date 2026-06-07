<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workout_exercise_id')->nullable()->constrained()->onDelete('set null');
            $table->date('workout_date');
            $table->integer('actual_sets')->default(0);
            $table->integer('actual_reps')->default(0);
            $table->integer('actual_weight_kg')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamp('logged_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_trackings');
    }
};
