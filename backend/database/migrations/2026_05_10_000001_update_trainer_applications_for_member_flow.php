<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trainer_applications')) {
            Schema::create('trainer_applications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->text('cv_document_url');
                $table->text('certificate_document_url');
                $table->string('status', 50)->default('pending')->index();
                $table->timestampTz('submitted_at')->useCurrent();
                $table->timestampTz('reviewed_at')->nullable();
                $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
                $table->text('admin_notes')->nullable();
                $table->index(['user_id', 'status']);
            });

            return;
        }

        Schema::table('trainer_applications', function (Blueprint $table) {
            if (! Schema::hasColumn('trainer_applications', 'certificate_document_url')) {
                $table->text('certificate_document_url')->nullable()->after('cv_document_url');
            }

            if (! Schema::hasColumn('trainer_applications', 'admin_notes')) {
                $table->text('admin_notes')->nullable();
            }
        });

        DB::statement("UPDATE trainer_applications SET status = 'pending' WHERE status IS NULL OR status = ''");
    }

    public function down(): void
    {
        if (! Schema::hasTable('trainer_applications')) {
            return;
        }

        Schema::table('trainer_applications', function (Blueprint $table) {
            if (Schema::hasColumn('trainer_applications', 'certificate_document_url')) {
                $table->dropColumn('certificate_document_url');
            }
        });
    }
};
