<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('system_logs')) {
            Schema::create('system_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
                $table->string('action_type')->nullable();
                $table->string('table_affected')->nullable();
                $table->unsignedBigInteger('record_id')->nullable();
                $table->text('description')->nullable();
                $table->string('ip_address')->nullable();
                $table->text('user_agent')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable();
            });

            return;
        }

        if (! Schema::hasColumn('system_logs', 'user_id')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'action_type')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->string('action_type')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'table_affected')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->string('table_affected')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'record_id')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('record_id')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'description')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->text('description')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'ip_address')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->string('ip_address')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'user_agent')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->text('user_agent')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'metadata')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->json('metadata')->nullable();
            });
        }

        if (! Schema::hasColumn('system_logs', 'created_at')) {
            Schema::table('system_logs', function (Blueprint $table) {
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
