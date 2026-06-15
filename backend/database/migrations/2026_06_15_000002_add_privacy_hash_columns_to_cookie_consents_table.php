<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cookie_consents')) {
            return;
        }

        Schema::table('cookie_consents', function (Blueprint $table) {
            if (! Schema::hasColumn('cookie_consents', 'last_updated_at')) {
                $table->timestamp('last_updated_at')->nullable()->after('consented_at');
            }

            if (! Schema::hasColumn('cookie_consents', 'ip_hash')) {
                $table->string('ip_hash', 128)->nullable()->after('withdrawn_at');
            }

            if (! Schema::hasColumn('cookie_consents', 'user_agent_hash')) {
                $table->string('user_agent_hash', 128)->nullable()->after('ip_hash');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('cookie_consents')) {
            return;
        }

        Schema::table('cookie_consents', function (Blueprint $table) {
            foreach (['last_updated_at', 'ip_hash', 'user_agent_hash'] as $column) {
                if (Schema::hasColumn('cookie_consents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
