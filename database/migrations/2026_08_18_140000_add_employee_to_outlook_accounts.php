<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('correos_outlook')) {
            return;
        }

        $hadColumn = Schema::hasColumn('correos_outlook', 'employee_id');
        if (! $hadColumn) {
            Schema::table('correos_outlook', function (Blueprint $table) {
                $table->integer('employee_id')->nullable()->after('id');
            });
        }

        if ($hadColumn && DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE `correos_outlook` MODIFY `employee_id` INT NULL');
        }
        $hasForeignKey = DB::connection()->getDriverName() === 'mysql'
            ? DB::table('information_schema.KEY_COLUMN_USAGE')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'correos_outlook')
                ->where('COLUMN_NAME', 'employee_id')
                ->whereNotNull('REFERENCED_TABLE_NAME')
                ->exists()
            : false;
        if (! $hasForeignKey) {
            Schema::table('correos_outlook', function (Blueprint $table) {
                $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('correos_outlook') && Schema::hasColumn('correos_outlook', 'employee_id')) {
            Schema::table('correos_outlook', function (Blueprint $table) {
                $table->dropForeign(['employee_id']);
                $table->dropColumn('employee_id');
            });
        }
    }
};
