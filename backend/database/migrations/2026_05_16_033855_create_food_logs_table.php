<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('food_logs')) {
            return;
        }

        Schema::create('food_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('food_name');
            $table->unsignedInteger('calories');
            $table->date('logged_date')->index();
            $table->timestamps();

            $table->index(['user_id', 'logged_date', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('food_logs');
    }
};
