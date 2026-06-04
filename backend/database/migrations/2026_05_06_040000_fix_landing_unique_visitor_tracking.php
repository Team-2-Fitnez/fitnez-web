<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('landing_page_visits')) {
            return;
        }

        Schema::table('landing_page_visits', function (Blueprint $table) {
            if (! Schema::hasColumn('landing_page_visits', 'browser_context')) {
                $table->string('browser_context', 40)->nullable()->index();
            }
        });

        DB::statement('ALTER TABLE landing_page_visits DROP CONSTRAINT IF EXISTS landing_unique_session_per_day');
        DB::statement('ALTER TABLE landing_page_visits DROP CONSTRAINT IF EXISTS landing_unique_visitor_per_day');

        /*
         * Merge duplicate rows from multiple tabs.
         * Keep the earliest row per visitor_uuid + visit_date.
         * Sum page views and keep latest last_seen_at.
         */
        DB::statement("
            WITH grouped AS (
                SELECT
                    visitor_uuid,
                    visit_date,
                    MIN(id) AS keep_id,
                    SUM(page_view_count) AS total_views,
                    MAX(last_seen_at) AS latest_seen,
                    MIN(visited_at) AS first_visit
                FROM landing_page_visits
                GROUP BY visitor_uuid, visit_date
                HAVING COUNT(*) > 1
            )
            UPDATE landing_page_visits l
            SET
                page_view_count = grouped.total_views,
                last_seen_at = grouped.latest_seen,
                visited_at = grouped.first_visit
            FROM grouped
            WHERE l.id = grouped.keep_id
        ");

        DB::statement("
            DELETE FROM landing_page_visits l
            USING landing_page_visits k
            WHERE
                l.visitor_uuid = k.visitor_uuid
                AND l.visit_date = k.visit_date
                AND l.id > k.id
        ");

        DB::statement("
            ALTER TABLE landing_page_visits
            ADD CONSTRAINT landing_unique_visitor_per_day
            UNIQUE (visitor_uuid, visit_date)
        ");
    }

    public function down(): void
    {
        if (! Schema::hasTable('landing_page_visits')) {
            return;
        }

        DB::statement('ALTER TABLE landing_page_visits DROP CONSTRAINT IF EXISTS landing_unique_visitor_per_day');
    }
};
