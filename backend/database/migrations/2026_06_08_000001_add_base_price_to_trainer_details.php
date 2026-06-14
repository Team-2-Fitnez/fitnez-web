<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trainer_details')) {
            return;
        }

        if (! Schema::hasColumn('trainer_details', 'base_price')) {
            Schema::table('trainer_details', function (Blueprint $table) {
                $table->decimal('base_price', 12, 2)->default(0)->after('hourly_rate');
            });

            if (Schema::hasColumn('trainer_details', 'trainer_session_price')) {
                DB::table('trainer_details')->update([
                    'base_price' => DB::raw('COALESCE(trainer_session_price, hourly_rate, 0)'),
                ]);
            } else {
                DB::table('trainer_details')->update([
                    'base_price' => DB::raw('COALESCE(hourly_rate, 0)'),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('trainer_details') || ! Schema::hasColumn('trainer_details', 'base_price')) {
            return;
        }

        Schema::table('trainer_details', function (Blueprint $table) {
            $table->dropColumn('base_price');
        });
    }
};
