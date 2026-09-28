<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('network_devices')) {
            Schema::create('network_devices', function (Blueprint $table) {
                $table->id();
                $table->string('device_name', 100);
                $table->string('device_type', 50)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('mac_address', 17)->nullable();
                $table->string('location', 100)->nullable();
                $table->string('brand', 50)->nullable();
                $table->string('status', 50)->default('Activo');
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('watchguard_users')) {
            Schema::create('watchguard_users', function (Blueprint $table) {
                $table->id();
                $table->string('username', 100);
                $table->string('password');
                $table->string('assigned_to', 150)->nullable();
                $table->string('area', 100)->nullable();
                $table->string('ip', 50)->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('enterprise_networks')) {
            Schema::create('enterprise_networks', function (Blueprint $table) {
                $table->id();
                $table->string('network_name', 100);
                $table->string('vlan', 10)->nullable();
                $table->string('location', 150)->nullable();
                $table->string('password', 100)->nullable();
                $table->string('encryption', 50)->nullable();
                $table->text('comments')->nullable();
                $table->text('notes_extra')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Imported network data is intentionally preserved.
    }
};
