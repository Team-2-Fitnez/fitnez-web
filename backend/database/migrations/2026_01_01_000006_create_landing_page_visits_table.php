<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('landing_page_visits')) {
            return;
        }

        Schema::create('landing_page_visits', function (Blueprint $table) {
            $table->id();
            $table->string('visitor_uuid', 120)->index();
            $table->string('session_uuid', 120)->index();
            $table->date('visit_date')->index();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('ip_address', 64)->nullable()->index();
            $table->string('user_agent_hash', 128)->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->string('browser_name', 80)->nullable()->index();
            $table->string('browser_version', 80)->nullable();
            $table->string('os_name', 80)->nullable()->index();
            $table->string('device_type', 40)->nullable()->index();

            $table->text('referrer')->nullable();
            $table->text('landing_url')->nullable();
            $table->string('route_path', 255)->nullable()->index();
            $table->jsonb('query_params')->nullable();

            $table->string('locale', 30)->nullable();
            $table->string('timezone', 80)->nullable();

            $table->unsignedInteger('screen_width')->nullable();
            $table->unsignedInteger('screen_height')->nullable();
            $table->unsignedInteger('viewport_width')->nullable();
            $table->unsignedInteger('viewport_height')->nullable();

            $table->unsignedInteger('page_view_count')->default(1);
            $table->timestampTz('visited_at')->index();
            $table->timestampTz('last_seen_at')->index();

            $table->timestampsTz();

            $table->unique(['visitor_uuid', 'session_uuid', 'visit_date'], 'landing_unique_session_per_day');
            $table->index(['last_seen_at', 'route_path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_visits');
    }
};
