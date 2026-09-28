<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hardware_assets') || Schema::hasColumn('hardware_assets', 'value')) {
            return;
        }

        Schema::table('hardware_assets', function (Blueprint $table) {
            $table->decimal('value', 12, 2)->nullable()->after('rustdesk_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('hardware_assets') && Schema::hasColumn('hardware_assets', 'value')) {
            Schema::table('hardware_assets', fn (Blueprint $table) => $table->dropColumn('value'));
        }
    }
};
