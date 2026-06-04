<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('user_devices')) return;
        Schema::create('user_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('device_uuid', 120);
            $table->string('platform', 40)->default('web');
            $table->string('browser', 120)->nullable();
            $table->string('user_agent_hash', 128)->nullable();
            $table->timestampTz('last_seen_at')->nullable()->index();
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->timestampsTz();
            $table->unique(['device_uuid','platform']);
        });
    }
    public function down(): void { Schema::dropIfExists('user_devices'); }
};
