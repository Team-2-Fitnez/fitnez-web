<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_page_visits')) {
            return;
        }

        Schema::table('landing_page_visits', function (Blueprint $table) {
            if (! Schema::hasColumn('landing_page_visits', 'browser_context')) {
                $table->string('browser_context', 80)->nullable()->index();
            }

            if (! Schema::hasColumn('landing_page_visits', 'browser_context_label')) {
                $table->string('browser_context_label', 120)->nullable();
            }

            if (! Schema::hasColumn('landing_page_visits', 'client_browser_name')) {
                $table->string('client_browser_name', 80)->nullable()->index();
            }

            if (! Schema::hasColumn('landing_page_visits', 'client_browser_engine')) {
                $table->string('client_browser_engine', 80)->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('landing_page_visits')) {
            return;
        }

        Schema::table('landing_page_visits', function (Blueprint $table) {
            foreach ([
                'browser_context',
                'browser_context_label',
                'client_browser_name',
                'client_browser_engine',
            ] as $column) {
                if (Schema::hasColumn('landing_page_visits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
