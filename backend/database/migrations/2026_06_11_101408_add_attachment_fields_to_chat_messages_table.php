<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (Schema::hasColumn('chat_messages', 'file_path')) {
                return;
            }

            $table->string('file_path')->nullable()->after('message');
            $table->string('file_name')->nullable()->after('file_path');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_name');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_messages', 'file_path')) {
                return;
            }

            $table->dropColumn(['file_path', 'file_name', 'file_size']);
        });
    }
};
