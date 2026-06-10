<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('trainer_details', 'base_price')) {
            return;
        }

        if (Schema::hasColumn('trainer_details', 'member_session_price')) {
            Schema::table('trainer_details', function (Blueprint $table) {
                $table->dropColumn(['member_session_price', 'trainer_session_price']);
            });
        }

        if (!Schema::hasColumn('trainer_details', 'base_price')) {
            Schema::table('trainer_details', function (Blueprint $table) {
                $table->decimal('base_price', 12, 2)->default(0)->after('hourly_rate');
            });
        }
    }

    public function down(): void
    {
        Schema::table('trainer_details', function (Blueprint $table) {
            $table->dropColumn('base_price');
        });

        Schema::table('trainer_details', function (Blueprint $table) {
            $table->decimal('member_session_price', 12, 2)->default(0)->after('hourly_rate');
            $table->decimal('trainer_session_price', 12, 2)->default(0)->after('member_session_price');
        });
    }
};
