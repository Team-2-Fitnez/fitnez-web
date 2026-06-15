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
            if (! Schema::hasColumn('landing_page_visits', 'private_mode_detected')) {
                $table->boolean('private_mode_detected')->nullable()->index();
            }

            if (! Schema::hasColumn('landing_page_visits', 'private_mode_confidence')) {
                $table->string('private_mode_confidence', 40)->nullable();
            }

            if (! Schema::hasColumn('landing_page_visits', 'private_mode_source')) {
                $table->string('private_mode_source', 80)->nullable();
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
                'private_mode_detected',
                'private_mode_confidence',
                'private_mode_source',
            ] as $column) {
                if (Schema::hasColumn('landing_page_visits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
