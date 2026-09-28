<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->string('full_name', 150);
                $table->string('department', 100)->nullable();
                $table->string('position', 100)->nullable();
                $table->string('email_corporate', 100)->nullable();
                $table->string('extension', 10)->nullable();
                $table->enum('status', ['Activo', 'Inactivo'])->default('Activo');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hardware_assets')) {
            Schema::create('hardware_assets', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable()->unique();
                $table->string('name', 100);
                $table->string('category', 50)->default('Equipo');
                $table->string('format', 100)->nullable();
                $table->string('brand', 50)->nullable();
                $table->string('model', 100)->nullable();
                $table->string('serial', 100)->nullable();
                $table->string('processor', 100)->nullable();
                $table->string('ram', 20)->nullable();
                $table->string('storage', 50)->nullable();
                $table->string('os', 100)->nullable();
                $table->string('os_version', 120)->nullable();
                $table->string('architecture', 100)->nullable();
                $table->string('bios')->nullable();
                $table->string('motherboard', 150)->nullable();
                $table->string('gpu', 150)->nullable();
                $table->string('network_adapter', 150)->nullable();
                $table->string('mac_address', 80)->nullable();
                $table->string('secure_boot', 80)->nullable();
                $table->string('tpm', 120)->nullable();
                $table->string('nfo_file')->nullable();
                $table->string('admin_password')->nullable();
                $table->string('status', 50)->default('DISPONIBLE');
                $table->string('location', 100)->nullable();
                $table->string('zone', 50)->nullable();
                $table->boolean('has_office')->default(false);
                $table->boolean('has_winrar')->default(false);
                $table->boolean('has_pdf_reader')->default(false);
                $table->boolean('has_reader')->default(false);
                $table->boolean('has_server')->default(false);
                $table->boolean('has_printer')->default(false);
                $table->date('delivery_date')->nullable();
                $table->text('comments')->nullable();
                $table->string('supply_type', 50)->nullable();
                $table->string('ink_type', 100)->nullable();
                $table->string('assigned_user', 150)->nullable();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('peripherals')) {
            Schema::create('peripherals', function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->nullable()->unique();
                $table->string('name', 100);
                $table->string('brand', 50)->nullable();
                $table->string('model', 100)->nullable();
                $table->string('serial', 100)->nullable();
                $table->string('category', 50)->nullable();
                $table->string('status', 50)->default('Disponible');
                $table->string('location', 100)->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->text('comments')->nullable();
                $table->string('assigned_to', 150)->nullable();
                $table->foreignId('computer_id')->nullable()->constrained('hardware_assets')->nullOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name', 150)->nullable();
                $table->string('role', 50)->nullable();
                $table->string('action', 50);
                $table->string('entity', 80);
                $table->string('entity_id', 80)->nullable();
                $table->longText('before_data')->nullable();
                $table->longText('after_data')->nullable();
                $table->string('ip_address', 64)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->index(['entity', 'entity_id']);
                $table->index('created_at');
            });
        }
    }

    public function down(): void
    {
        // Baseline tables may contain imported production data and are never dropped automatically.
    }
};
