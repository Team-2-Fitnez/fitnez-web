<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasColumn('prospective_member_registrations', 'birth_date')) {
            Schema::table('prospective_member_registrations', function (Blueprint $table) {
                $table->date('birth_date')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        Schema::table('prospective_member_registrations', function (Blueprint $table) {
            $table->dropColumn('birth_date');
        });
    }
};
