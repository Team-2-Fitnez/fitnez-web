<?php
use App\Services\Membership\MembershipPackageFactory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('membership_packages')) {
            Schema::create('membership_packages', function (Blueprint $table) {
                $table->id(); $table->string('code',50)->unique(); $table->string('name',120);
                $table->unsignedSmallInteger('duration_months'); $table->unsignedBigInteger('price');
                $table->boolean('free_class_access')->default(false); $table->jsonb('benefits')->nullable();
                $table->boolean('is_active')->default(true); $table->timestampsTz();
            });
        }
        foreach (MembershipPackageFactory::defaultPackages() as $p) {
            DB::table('membership_packages')->updateOrInsert(['code'=>$p['code']], [
                'name'=>$p['name'], 'duration_months'=>$p['duration_months'], 'price'=>$p['price'],
                'free_class_access'=>$p['free_class_access'], 'benefits'=>json_encode($p['benefits']),
                'is_active'=>true, 'created_at'=>now(), 'updated_at'=>now()
            ]);
        }
    }
    public function down(): void { Schema::dropIfExists('membership_packages'); }
};
