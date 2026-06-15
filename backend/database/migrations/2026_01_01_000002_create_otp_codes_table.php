<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('otp_codes')) return;
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('email')->index();
            $table->string('purpose', 50)->index();
            $table->string('code_hash');
            $table->timestampTz('expires_at')->index();
            $table->timestampTz('used_at')->nullable()->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('ip_address', 64)->nullable();
            $table->timestampsTz();
            $table->index(['email','purpose','used_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('otp_codes'); }
};
