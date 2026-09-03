<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('reminder_morning_notified_at')->nullable()->after('due_today_notified_at');
            $table->timestamp('reminder_evening_notified_at')->nullable()->after('reminder_morning_notified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['reminder_morning_notified_at', 'reminder_evening_notified_at']);
        });
    }
};
