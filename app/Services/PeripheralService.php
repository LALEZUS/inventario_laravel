<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Peripheral;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PeripheralService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function store(array $data, ?User $user = null): Peripheral
    {
        $data = $this->prepareData($data);

        return DB::transaction(function () use ($data) {
            $peripheral = Peripheral::create($data);
            $this->audit->record('create', 'peripherals', $peripheral->id, null, $peripheral->getAttributes());
            return $peripheral;
        });
    }

    public function update(Peripheral $peripheral, array $data, ?User $user = null): Peripheral
    {
        $before = $peripheral->getAttributes();
        $data = $this->prepareData($data);

        return DB::transaction(function () use ($peripheral, $data, $before) {
            $peripheral->update($data);
            $fresh = $peripheral->fresh();
            $this->audit->record('update', 'peripherals', $peripheral->id, $before, $fresh->getAttributes());
            return $fresh;
        });
    }

    public function delete(Peripheral $peripheral, ?User $user = null): void
    {
        $before = $peripheral->getAttributes();

        DB::transaction(function () use ($peripheral, $before) {
            $peripheral->delete();
            $this->audit->record('delete', 'peripherals', $peripheral->id, $before, null);
        });
    }

    private function prepareData(array $data): array
    {
        if (filled($data['employee_id'] ?? null)) {
            $employee = Employee::find($data['employee_id']);
            if ($employee) {
                $data['assigned_to'] = $employee->full_name;
            }
        }

        return $data;
    }
}
