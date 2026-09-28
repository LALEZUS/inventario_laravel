<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->index('employees', ['status', 'department'], 'employees_status_department_index');
        $this->index('hardware_assets', ['employee_id', 'status'], 'hardware_assets_employee_status_index');
        $this->index('hardware_assets', ['zone', 'updated_at'], 'hardware_assets_zone_updated_index');
        $this->index('cellphones', ['employee_id', 'status'], 'cellphones_employee_status_index');
        $this->index('peripherals', ['computer_id', 'employee_id'], 'peripherals_computer_employee_index');
        $this->index('peripherals', ['status', 'updated_at'], 'peripherals_status_updated_index');
        $this->index('printers', ['status', 'zone'], 'printers_status_zone_index');
        $this->index('inks', ['quantity', 'status'], 'inks_quantity_status_index');
        $this->index('toner', ['quantity', 'status'], 'toner_quantity_status_index');
        $this->index('licenses', ['status', 'expiration_date'], 'licenses_status_expiration_index');
        $this->index('maintenance_logs', ['asset_type', 'next_date'], 'maintenance_asset_next_date_index');
        $this->index('calendar_reminders', ['event_date', 'is_done'], 'calendar_event_done_index');
        $this->index('correos_outlook', ['estatus', 'updated_at'], 'outlook_status_updated_index');
        $this->index('asset_files', ['asset_type', 'asset_id', 'uploaded_at'], 'asset_files_asset_uploaded_index');
    }

    private function index(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($table, $column)) {
                return;
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $name): void {
            $blueprint->index($columns, $name);
        });
    }
};
