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
            if (! Schema::hasColumn('payments', 'payment_proof_path')) {
                $table->string('payment_proof_path')->nullable()->after(Schema::hasColumn('payments', 'external_reference') ? 'external_reference' : 'payment_status');
            }
            if (! Schema::hasColumn('payments', 'membership_package_id')) {
                $table->foreignId('membership_package_id')
                    ->nullable()
                    ->after(Schema::hasColumn('payments', 'booking_id') ? 'booking_id' : 'user_id')
                    ->constrained('membership_packages')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        if (Schema::hasColumn('payments', 'membership_package_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('membership_package_id');
            });
        }
        if (Schema::hasColumn('payments', 'payment_proof_path')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('payment_proof_path');
            });
        }
    }
};
