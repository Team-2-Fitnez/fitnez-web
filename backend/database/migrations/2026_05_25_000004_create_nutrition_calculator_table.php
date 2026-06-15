<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nutrition_calculator')) {
            Schema::create('nutrition_calculator', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->decimal('bmr', 10, 2)->default(0);
                $table->decimal('tdee', 10, 2)->default(0);
                $table->decimal('target_calories', 10, 2)->default(0);
                $table->decimal('target_protein', 10, 2)->default(0);
                $table->decimal('target_carbs', 10, 2)->default(0);
                $table->decimal('target_fat', 10, 2)->default(0);
                $table->timestamp('calculated_at')->useCurrent();
            });

            return;
        }

        Schema::table('nutrition_calculator', function (Blueprint $table) {
            if (! Schema::hasColumn('nutrition_calculator', 'user_id')) {
                $table->unsignedBigInteger('user_id');
            }

            if (! Schema::hasColumn('nutrition_calculator', 'bmr')) {
                $table->decimal('bmr', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('nutrition_calculator', 'tdee')) {
                $table->decimal('tdee', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('nutrition_calculator', 'target_calories')) {
                $table->decimal('target_calories', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('nutrition_calculator', 'target_protein')) {
                $table->decimal('target_protein', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('nutrition_calculator', 'target_carbs')) {
                $table->decimal('target_carbs', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('nutrition_calculator', 'target_fat')) {
                $table->decimal('target_fat', 10, 2)->default(0);
            }

            if (! Schema::hasColumn('nutrition_calculator', 'calculated_at')) {
                $table->timestamp('calculated_at')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        // This table may be created by another migration, so do not drop it here.
    }
};
