<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cookie_consents')) {
            return;
        }

        Schema::create('cookie_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('anonymous_id')->nullable()->index();
            $table->string('consent_version')->default('1.0');
            $table->boolean('essential')->default(true);
            $table->boolean('analytics')->default(false);
            $table->boolean('marketing')->default(false);
            $table->boolean('preferences')->default(false);
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'consent_version']);
            $table->index(['anonymous_id', 'consent_version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cookie_consents');
    }
};
