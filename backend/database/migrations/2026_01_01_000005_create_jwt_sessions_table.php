<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('jwt_sessions')) {
            return;
        }

        Schema::create('jwt_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('jti', 120)->unique();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent_hash', 128)->nullable();
            $table->timestampTz('issued_at');
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('revoked_at')->nullable()->index();
            $table->timestampsTz();

            $table->index(['user_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jwt_sessions');
    }
};
