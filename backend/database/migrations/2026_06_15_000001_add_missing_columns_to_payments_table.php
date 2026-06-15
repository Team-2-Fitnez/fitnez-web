<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'membership_package_id')) {
                $table->foreignId('membership_package_id')
                    ->nullable()
                    ->after('booking_id')
                    ->constrained('membership_packages')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('payments', 'payment_proof_path')) {
                $table->text('payment_proof_path')->nullable()->after('external_reference');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'membership_package_id')) {
                $table->dropConstrainedForeignId('membership_package_id');
            }

            if (Schema::hasColumn('payments', 'payment_proof_path')) {
                $table->dropColumn('payment_proof_path');
            }
        });
    }
};
