<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('users')) return;
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'email_verified_at')) $table->timestampTz('email_verified_at')->nullable()->after('is_active');
        });
    }
    public function down(): void {
        if (!Schema::hasTable('users')) return;
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'email_verified_at')) $table->dropColumn('email_verified_at');
        });
    }
};
