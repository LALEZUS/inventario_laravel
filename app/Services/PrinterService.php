<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Printer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PrinterService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): Printer
    {
        $data = $this->prepareData($data);

        return DB::transaction(function () use ($data) {
            $printer = Printer::create($data);
            $this->audit->record('create', 'printers', $printer->id, null, $printer->getAttributes());
            return $printer;
        });
    }

    public function update(Printer $printer, array $data, ?User $user = null): Printer
    {
        $before = $printer->getAttributes();
        $data = $this->prepareData($data);

        return DB::transaction(function () use ($printer, $data, $before) {
            $printer->update($data);
            $fresh = $printer->fresh();
            $this->audit->record('update', 'printers', $printer->id, $before, $fresh->getAttributes());
            return $fresh;
        });
    }

    public function delete(Printer $printer, ?User $user = null): void
    {
        $before = $printer->getAttributes();

        DB::transaction(function () use ($printer, $before) {
            $printer->delete();
            $this->audit->record('delete', 'printers', $printer->id, $before, null);
        });
    }

    public function prepareData(array $data): array
    {
        $isNetwork = filter_var($data['is_network'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['is_network'] = $isNetwork;

        if (! $isNetwork) {
            $data['ip_address'] = null;
        }

        if (array_key_exists('linked_inks', $data) && is_array($data['linked_inks'])) {
            $data['linked_inks'] = array_values(array_filter(array_map('intval', $data['linked_inks'])));
        }

        if (array_key_exists('linked_toner', $data) && is_array($data['linked_toner'])) {
            $data['linked_toner'] = array_values(array_filter(array_map('intval', $data['linked_toner'])));
        }

        if (filled($data['employee_id'] ?? null)) {
            $employee = Employee::find($data['employee_id']);
            if ($employee) {
                $data['assigned_to'] = $employee->full_name;
            }
        }

        return $data;
    }
}