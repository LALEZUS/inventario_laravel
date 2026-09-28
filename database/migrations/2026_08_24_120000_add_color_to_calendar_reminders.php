<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('calendar_reminders') && ! Schema::hasColumn('calendar_reminders', 'color')) {
            Schema::table('calendar_reminders', function (Blueprint $table): void {
                $table->string('color', 7)->default('#E31B23')->after('title');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('calendar_reminders') && Schema::hasColumn('calendar_reminders', 'color')) {
            Schema::table('calendar_reminders', function (Blueprint $table): void {
                $table->dropColumn('color');
            });
        }
    }
};
