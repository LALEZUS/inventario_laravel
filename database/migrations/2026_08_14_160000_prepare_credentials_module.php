<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('account_management')) {
            Schema::create('account_management', function (Blueprint $table) {
                $table->id();
                $table->string('email', 150)->unique();
                $table->string('password')->nullable();
                $table->enum('account_type', ['Microsoft 365', 'Gmail', 'Hospedaje', 'Personal']);
                $table->string('assigned_to', 150)->nullable();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('status', 50)->default('Activo');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('correos_outlook')) {
            Schema::create('correos_outlook', function (Blueprint $table) {
                $table->id();
                $table->string('correo', 150)->unique();
                $table->string('contraseña');
                $table->enum('estatus', ['ACTIVA', 'BAJA'])->default('ACTIVA');
                $table->text('comentarios')->nullable();
                $table->timestamps();
                $table->string('servidor_entrada')->default('');
                $table->string('puerto_entrada', 20)->default('');
                $table->boolean('ssl_entrada')->default(false);
                $table->string('servidor_salida')->default('');
                $table->string('puerto_salida', 20)->default('');
                $table->string('cifrado_salida', 50)->default('SSL/TLS');
            });
        }

        if (! Schema::hasTable('licenses')) {
            Schema::create('licenses', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('type', 50)->nullable();
                $table->text('key_value')->nullable();
                $table->string('password')->nullable();
                $table->date('expiration_date')->nullable();
                $table->string('status', 50)->nullable();
                $table->string('vendor', 100)->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
                $table->text('link')->nullable();
            });
        }
    }

    public function down(): void
    {
        // Imported credential data is intentionally preserved.
    }
};
