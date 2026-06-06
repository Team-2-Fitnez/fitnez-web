<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('exercises')) {
            Schema::create('exercises', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('muscle_group')->nullable();
                $table->string('equipment')->nullable();
                $table->string('tutorial_video_url')->nullable();
                $table->string('tutorial_image_url')->nullable();
                $table->text('instructions')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            });

            return;
        }

        Schema::table('exercises', function (Blueprint $table) {
            if (! Schema::hasColumn('exercises', 'muscle_group')) {
                $table->string('muscle_group')->nullable();
            }

            if (! Schema::hasColumn('exercises', 'equipment')) {
                $table->string('equipment')->nullable();
            }

            if (! Schema::hasColumn('exercises', 'tutorial_video_url')) {
                $table->string('tutorial_video_url')->nullable();
            }

            if (! Schema::hasColumn('exercises', 'tutorial_image_url')) {
                $table->string('tutorial_image_url')->nullable();
            }

            if (! Schema::hasColumn('exercises', 'instructions')) {
                $table->text('instructions')->nullable();
            }

            if (! Schema::hasColumn('exercises', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('exercises', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        // This table may be created by another migration, so do not drop it here.
    }
};
