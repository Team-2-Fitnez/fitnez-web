<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_page_visits')) {
            return;
        }

        Schema::table('landing_page_visits', function (Blueprint $table) {
            if (! Schema::hasColumn('landing_page_visits', 'visit_date')) {
                $table->date('visit_date')->nullable()->index();
            }

            if (! Schema::hasColumn('landing_page_visits', 'last_seen_at')) {
                $table->timestampTz('last_seen_at')->nullable()->index();
            }

            if (! Schema::hasColumn('landing_page_visits', 'page_view_count')) {
                $table->unsignedInteger('page_view_count')->default(1);
            }
        });

        DB::statement("UPDATE landing_page_visits SET visit_date = DATE(visited_at) WHERE visit_date IS NULL");
        DB::statement("UPDATE landing_page_visits SET last_seen_at = visited_at WHERE last_seen_at IS NULL");
    }

    public function down(): void
    {
        if (! Schema::hasTable('landing_page_visits')) {
            return;
        }

        Schema::table('landing_page_visits', function (Blueprint $table) {
            if (Schema::hasColumn('landing_page_visits', 'visit_date')) {
                $table->dropColumn('visit_date');
            }

            if (Schema::hasColumn('landing_page_visits', 'last_seen_at')) {
                $table->dropColumn('last_seen_at');
            }

            if (Schema::hasColumn('landing_page_visits', 'page_view_count')) {
                $table->dropColumn('page_view_count');
            }
        });
    }
};
