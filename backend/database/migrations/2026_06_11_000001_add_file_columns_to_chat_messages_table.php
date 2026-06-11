<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chat_messages', 'file_url')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            $table->text('file_url')->nullable()->after('message');
            $table->string('file_name')->nullable()->after('file_url');
            $table->integer('file_size')->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn(['file_url', 'file_name', 'file_size']);
        });
    }
};
