<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('browser_tabs')) return;
        Schema::create('browser_tabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_uuid', 120)->index();
            $table->string('tab_id', 120)->index();
            $table->string('context_type', 30)->default('normal')->index();
            $table->string('route')->nullable();
            $table->boolean('is_leader')->default(false)->index();
            $table->timestampTz('last_heartbeat_at')->nullable()->index();
            $table->timestampsTz();
            $table->unique(['device_uuid','tab_id','context_type']);
            $table->index(['user_id','device_uuid','context_type','is_leader']);
        });
    }
    public function down(): void { Schema::dropIfExists('browser_tabs'); }
};
