<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('prospective_member_registrations')) { return; }
        Schema::create('prospective_member_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('membership_package_id')->constrained('membership_packages')->restrictOnDelete();
            $table->foreignId('manual_payment_method_id')->constrained('manual_payment_methods')->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('registration_code',120)->unique(); $table->string('full_name',100); $table->string('email',255)->index(); $table->string('phone',30)->nullable(); $table->string('password_hash',255);
            $table->unsignedBigInteger('amount'); $table->string('status',50)->default('awaiting_payment')->index();
            $table->text('payment_proof_path')->nullable(); $table->timestampTz('payment_submitted_at')->nullable();
            $table->timestampTz('approved_at')->nullable(); $table->timestampTz('rejected_at')->nullable(); $table->text('rejection_reason')->nullable(); $table->timestampTz('account_created_at')->nullable();
            $table->timestampsTz(); $table->index(['email','status']);
        });
    }
    public function down(): void { Schema::dropIfExists('prospective_member_registrations'); }
};
