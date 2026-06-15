<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('manual_payment_methods')) {
            Schema::create('manual_payment_methods', function (Blueprint $table) {
                $table->id(); $table->string('code',50)->unique(); $table->string('type',30); $table->string('display_name',120);
                $table->string('bank_name',80)->nullable(); $table->string('account_number',80)->nullable(); $table->string('account_name',120)->nullable();
                $table->text('qris_image_url')->nullable(); $table->text('instructions')->nullable(); $table->boolean('is_active')->default(true); $table->timestampsTz();
            });
        }
        DB::table('manual_payment_methods')->updateOrInsert(['code'=>'QRIS_FITNEZ'], [
            'type'=>'qris','display_name'=>'QRIS Fitnez','account_name'=>'FITNEZ GYM',
            'qris_image_url'=>'/images/payment/qris-fitnez-placeholder.svg',
            'instructions'=>'Scan QRIS, input the package price manually, then upload proof.', 'is_active'=>true,
            'created_at'=>now(),'updated_at'=>now()
        ]);
        DB::table('manual_payment_methods')->updateOrInsert(['code'=>'BANK_TRANSFER_BCA'], [
            'type'=>'bank_transfer','display_name'=>'Bank Transfer BCA','bank_name'=>'BCA','account_number'=>'1234567890','account_name'=>'FITNEZ GYM',
            'instructions'=>'Transfer the package price to this account, then upload proof.', 'is_active'=>true,
            'created_at'=>now(),'updated_at'=>now()
        ]);
    }
    public function down(): void { Schema::dropIfExists('manual_payment_methods'); }
};
