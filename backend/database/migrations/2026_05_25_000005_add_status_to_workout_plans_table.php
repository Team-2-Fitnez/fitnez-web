<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('workout_plans') && ! Schema::hasColumn('workout_plans', 'status')) {
            Schema::table('workout_plans', function (Blueprint $table) {
                $table->string('status')->nullable()->after('completed');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('workout_plans') && Schema::hasColumn('workout_plans', 'status')) {
            Schema::table('workout_plans', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
