<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hardware_assets')) {
            return;
        }

        Schema::table('hardware_assets', function (Blueprint $table) {
            if (! Schema::hasColumn('hardware_assets', 'anydesk_id')) {
                $table->string('anydesk_id', 50)->nullable()->after('admin_password');
            }

            if (! Schema::hasColumn('hardware_assets', 'rustdesk_id')) {
                $table->string('rustdesk_id', 100)->nullable()->after('anydesk_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hardware_assets')) {
            return;
        }

        Schema::table('hardware_assets', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['anydesk_id', 'rustdesk_id'],
                fn (string $column): bool => Schema::hasColumn('hardware_assets', $column),
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
