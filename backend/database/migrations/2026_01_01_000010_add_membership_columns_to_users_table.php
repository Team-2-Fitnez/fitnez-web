<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'membership_package_id')) {
                $table->foreignId('membership_package_id')->nullable()->constrained('membership_packages')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'membership_started_at')) {
                $table->timestampTz('membership_started_at')->nullable();
            }

            if (! Schema::hasColumn('users', 'membership_expires_at')) {
                $table->timestampTz('membership_expires_at')->nullable()->index();
            }

            if (! Schema::hasColumn('users', 'free_class_access')) {
                $table->boolean('free_class_access')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'membership_package_id')) {
                $table->dropConstrainedForeignId('membership_package_id');
            }

            foreach (['membership_started_at', 'membership_expires_at', 'free_class_access'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
