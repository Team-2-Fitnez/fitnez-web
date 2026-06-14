<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'age')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('age')->nullable()->after(Schema::hasColumn('users', 'birth_date') ? 'birth_date' : 'phone');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'age')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('age');
        });
    }
};
