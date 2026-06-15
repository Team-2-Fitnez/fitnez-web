<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workout_plans')) {
            return;
        }

        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->date('date')->index();
            $table->string('day')->nullable();
            $table->unsignedInteger('set')->default(1);
            $table->decimal('weight', 8, 2)->default(0);
            $table->unsignedInteger('reps')->default(1);
            $table->unsignedInteger('duration')->nullable();
            $table->boolean('completed')->default(false)->index();
            $table->string('status')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'date', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_plans');
    }
};
