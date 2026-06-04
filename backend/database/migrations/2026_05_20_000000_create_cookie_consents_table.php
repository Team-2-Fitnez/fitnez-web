<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->uuid('anonymous_id')->index();
            $table->string('consent_version', 40);
            $table->boolean('essential')->default(true);
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->boolean('preferences')->default(false);
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent_hash')->nullable();
            $table->timestamps();

            $table->index(['anonymous_id', 'consent_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
