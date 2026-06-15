<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('push_subscriptions')) {
            Schema::create('push_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->text('endpoint');
                $table->text('auth_key');
                $table->text('p256dh_key');
                $table->timestamps();

                $table->unique(['user_id', 'endpoint']);
            });

            return;
        }

        Schema::table('push_subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('push_subscriptions', 'auth_key')) {
                $table->text('auth_key')->nullable()->after('endpoint');
            }
            if (! Schema::hasColumn('push_subscriptions', 'p256dh_key')) {
                $table->text('p256dh_key')->nullable()->after('auth_key');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
    }
};
