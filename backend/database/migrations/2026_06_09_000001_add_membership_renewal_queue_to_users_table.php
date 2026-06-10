<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'renewal_package_id')) {
                $table->foreignId('renewal_package_id')
                    ->nullable()
                    ->after('free_class_access')
                    ->constrained('membership_packages')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'membership_renewal_starts_at')) {
                $table->timestampTz('membership_renewal_starts_at')->nullable()->after('renewal_package_id');
            }

            if (! Schema::hasColumn('users', 'membership_renewal_expires_at')) {
                $table->timestampTz('membership_renewal_expires_at')->nullable()->after('membership_renewal_starts_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'renewal_package_id')) {
                $table->dropConstrainedForeignId('renewal_package_id');
            }

            foreach (['membership_renewal_expires_at', 'membership_renewal_starts_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
