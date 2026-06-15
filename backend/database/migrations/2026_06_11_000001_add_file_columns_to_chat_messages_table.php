<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_messages')) {
            return;
        }

        Schema::table('chat_messages', function (Blueprint $table) {
            if (! Schema::hasColumn('chat_messages', 'file_path')) {
                $table->string('file_path')->nullable()->after('message');
            }
            if (! Schema::hasColumn('chat_messages', 'file_url')) {
                $table->text('file_url')->nullable()->after(Schema::hasColumn('chat_messages', 'file_path') ? 'file_path' : 'message');
            }
            if (! Schema::hasColumn('chat_messages', 'file_name')) {
                $table->string('file_name')->nullable()->after(Schema::hasColumn('chat_messages', 'file_url') ? 'file_url' : 'file_path');
            }
            if (! Schema::hasColumn('chat_messages', 'file_type')) {
                $table->string('file_type')->nullable()->after('file_name');
            }
            if (! Schema::hasColumn('chat_messages', 'file_size')) {
                $table->unsignedBigInteger('file_size')->nullable()->after('file_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('chat_messages')) {
            return;
        }

        foreach (['file_path', 'file_url', 'file_name', 'file_type', 'file_size'] as $column) {
            if (Schema::hasColumn('chat_messages', $column)) {
                Schema::table('chat_messages', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
