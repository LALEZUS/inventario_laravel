<?php

namespace App\Services;

use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CellphoneService
{
    private const SECRET_FIELDS = ['password', 'updated_password', 'app_lock_password', 'app_lock_answer', 'app_lock_pattern'];

    public function __construct(
        private readonly AuditLogger $audit
    ) {}

    /**
     * Store a new Cellphone with transaction and audit logging.
     */
    public function store(array $validatedData, bool $hasAppLock, User $performer): Cellphone
    {
        $data = $this->prepareData($validatedData, $hasAppLock);

        return DB::transaction(function () use ($data, $performer) {
            $cellphone = Cellphone::create($data);
            $this->audit->record('create', 'cellphones', $cellphone->id, null, $cellphone->getAttributes(), $performer);

            return $cellphone;
        });
    }

    /**
     * Update an existing Cellphone with lock & audit logging.
     */
    public function update(int $cellphoneId, array $validatedData, bool $hasAppLock, User $performer): Cellphone
    {
        return DB::transaction(function () use ($cellphoneId, $validatedData, $hasAppLock, $performer) {
            $cellphone = Cellphone::whereKey($cellphoneId)->lockForUpdate()->firstOrFail();
            $before = $cellphone->getAttributes();

            $data = $this->prepareData($validatedData, $hasAppLock, $cellphone);
            $cellphone->update($data);

            $this->audit->record('update', 'cellphones', $cellphone->id, $before, $cellphone->fresh()->getAttributes(), $performer);

            return $cellphone->fresh();
        });
    }

    /**
     * Prepare data array from validated request.
     */
    public function prepareData(array $validatedData, bool $hasAppLock, ?Cellphone $existing = null): array
    {
        $data = $validatedData;
        $submittedLockData = collect(['app_lock_password', 'app_lock_answer', 'app_lock_pattern'])
            ->contains(fn (string $field) => filled($data[$field] ?? null));
        $data['has_app_lock'] = $hasAppLock || $submittedLockData;

        foreach (self::SECRET_FIELDS as $field) {
            if ($existing && blank($data[$field] ?? null)) {
                $data[$field] = $existing->{$field};
            }
        }

        if (! $data['has_app_lock']) {
            $data['app_lock_password'] = null;
            $data['app_lock_answer'] = null;
            $data['app_lock_pattern'] = null;
        }

        if (filled($data['employee_id'] ?? null)) {
            $employee = Employee::findOrFail($data['employee_id']);
            $data['employee_name_legacy'] = $employee->full_name;
            $data['area'] = ($data['area'] ?? null) ?: $employee->department;
        }

        return $data;
    }
}
