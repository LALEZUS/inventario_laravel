<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inks') && ! Schema::hasColumn('inks', 'low_stock_threshold')) {
            Schema::table('inks', function (Blueprint $table) {
                $table->unsignedInteger('low_stock_threshold')->default(2)->after('quantity');
            });
        }

        if (Schema::hasTable('toner') && ! Schema::hasColumn('toner', 'low_stock_threshold')) {
            Schema::table('toner', function (Blueprint $table) {
                $table->unsignedInteger('low_stock_threshold')->default(2)->after('quantity');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('inks') && Schema::hasColumn('inks', 'low_stock_threshold')) {
            Schema::table('inks', fn (Blueprint $table) => $table->dropColumn('low_stock_threshold'));
        }

        if (Schema::hasTable('toner') && Schema::hasColumn('toner', 'low_stock_threshold')) {
            Schema::table('toner', fn (Blueprint $table) => $table->dropColumn('low_stock_threshold'));
        }
    }
};
