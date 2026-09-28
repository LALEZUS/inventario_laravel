<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('employees') && ! Schema::hasColumn('employees', 'phone_number')) {
            Schema::table('employees', function (Blueprint $table): void {
                $table->string('phone_number', 25)->nullable()->after('email_corporate');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('employees') && Schema::hasColumn('employees', 'phone_number')) {
            Schema::table('employees', fn (Blueprint $table) => $table->dropColumn('phone_number'));
        }
    }
};
