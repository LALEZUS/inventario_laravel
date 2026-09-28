<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inks')) {
            Schema::create('inks', function (Blueprint $table) {
                $table->id();
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->string('color')->nullable();
                $table->string('type')->nullable();
                $table->string('capacity')->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->date('purchase_date')->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('status', 50)->default('Disponible');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('toner')) {
            Schema::create('toner', function (Blueprint $table) {
                $table->id();
                $table->string('brand')->nullable();
                $table->string('model')->nullable();
                $table->unsignedInteger('quantity')->default(0);
                $table->string('status', 50)->default('NUEVO');
                $table->text('comentarios')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('printers')) {
            Schema::create('printers', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('brand', 100)->nullable();
                $table->string('model', 100)->nullable();
                $table->string('serial', 100)->nullable();
                $table->string('code', 50)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('zone', 100)->nullable();
                $table->string('assigned_to', 150)->nullable();
                $table->boolean('is_network')->default(false);
                $table->string('supply_type', 50)->nullable();
                $table->string('ink_type', 100)->nullable();
                $table->text('linked_inks')->nullable();
                $table->text('linked_toner')->nullable();
                $table->string('status', 50)->default('Activo');
                $table->text('comments')->nullable();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamps();
            });
        } elseif (! Schema::hasColumn('printers', 'employee_id')) {
            Schema::table('printers', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        // Existing and imported printer data is intentionally preserved.
    }
};
