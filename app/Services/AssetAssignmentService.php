<?php

namespace App\Services;

use App\Models\Assignment;
use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssetAssignmentService
{
    public function __construct(
        private readonly AuditLogger $audit
    ) {}

    /**
     * Assign a hardware asset (computer) to an employee with concurrency protection.
     */
    public function assignComputer(int $computerId, array $data, Employee $employee, User $performer): Assignment
    {
        return DB::transaction(function () use ($computerId, $data, $employee, $performer) {
            // Lock the target asset record before validating state
            $computer = HardwareAsset::whereKey($computerId)->lockForUpdate()->firstOrFail();

            if ($computer->status === 'ENTREGADO') {
                throw ValidationException::withMessages([
                    'computer' => ['El equipo ya se encuentra asignado a un empleado (ENTREGADO).'],
                ]);
            }

            if (Assignment::where('asset_type', 'inventory')
                ->where('asset_id', $computer->id)
                ->whereNull('date_returned')
                ->exists()) {
                throw ValidationException::withMessages([
                    'computer' => ['El equipo ya tiene una asignacion activa. Registra primero la devolucion.'],
                ]);
            }

            $assignment = Assignment::create([
                'asset_type' => 'inventory',
                'asset_id' => $computer->id,
                'employee_id' => $employee->id,
                'assigned_to' => $employee->full_name,
                'department' => ($data['department'] ?? null) ?: $employee->department,
                'date_assigned' => $data['date_assigned'],
                'condition_on_assign' => $data['condition_on_assign'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $before = $computer->getAttributes();
            $computer->update([
                'employee_id' => $employee->id,
                'assigned_user' => $employee->full_name,
                'delivery_date' => $data['date_assigned'],
                'status' => 'ENTREGADO',
            ]);

            $this->audit->record('assign', 'assignments', $assignment->id, null, $assignment->getAttributes(), $performer);
            $this->audit->record('update', 'hardware_assets', $computer->id, $before, $computer->fresh()->getAttributes(), $performer);

            return $assignment;
        });
    }

    /**
     * Reassign a cellphone while preserving the previous holder in the shared assignment history.
     */
    public function reassignCellphone(int $cellphoneId, array $data, Employee $employee, User $performer): Assignment
    {
        return DB::transaction(function () use ($cellphoneId, $data, $employee, $performer) {
            $cellphone = Cellphone::whereKey($cellphoneId)->lockForUpdate()->firstOrFail();
            $assignmentDate = $data['date_assigned'];

            $activeAssignment = Assignment::query()
                ->where('asset_type', 'cellphone')
                ->where('asset_id', $cellphone->id)
                ->whereNull('date_returned')
                ->lockForUpdate()
                ->latest('date_assigned')
                ->latest('id')
                ->first();

            if ($activeAssignment && (int) $activeAssignment->employee_id === (int) $employee->id) {
                throw ValidationException::withMessages([
                    'employee_id' => ['Este celular ya esta asignado a ese empleado.'],
                ]);
            }

            if ($activeAssignment && $assignmentDate < $activeAssignment->date_assigned->format('Y-m-d')) {
                throw ValidationException::withMessages([
                    'date_assigned' => ['La nueva fecha no puede ser anterior a la asignacion vigente.'],
                ]);
            }

            if ($activeAssignment) {
                $beforeAssignment = $activeAssignment->getAttributes();
                $activeAssignment->update([
                    'date_returned' => $assignmentDate,
                    'condition_on_return' => 'Reasignado',
                    'comments' => 'Cierre automatico por nueva asignacion.',
                ]);
                $this->audit->record('return', 'assignments', $activeAssignment->id, $beforeAssignment, $activeAssignment->fresh()->getAttributes(), $performer);
            } elseif (filled($cellphone->assigned_name)) {
                if ((int) $cellphone->employee_id === (int) $employee->id) {
                    throw ValidationException::withMessages([
                        'employee_id' => ['Este celular ya esta asignado a ese empleado.'],
                    ]);
                }

                $createdDate = $cellphone->created_at?->format('Y-m-d');
                $previousAssignment = Assignment::create([
                    'asset_type' => 'cellphone',
                    'asset_id' => $cellphone->id,
                    'employee_id' => $cellphone->employee_id,
                    'assigned_to' => $cellphone->assigned_name,
                    'department' => $cellphone->area,
                    'date_assigned' => $createdDate && $createdDate <= $assignmentDate ? $createdDate : $assignmentDate,
                    'date_returned' => $assignmentDate,
                    'condition_on_return' => 'Reasignado',
                    'notes' => 'Registro historico generado al realizar la primera reasignacion.',
                ]);
                $this->audit->record('history', 'assignments', $previousAssignment->id, null, $previousAssignment->getAttributes(), $performer);
            }

            $assignment = Assignment::create([
                'asset_type' => 'cellphone',
                'asset_id' => $cellphone->id,
                'employee_id' => $employee->id,
                'assigned_to' => $employee->full_name,
                'department' => ($data['department'] ?? null) ?: $employee->department,
                'date_assigned' => $assignmentDate,
                'condition_on_assign' => $data['condition_on_assign'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $beforeCellphone = $cellphone->getAttributes();
            $cellphone->update([
                'employee_id' => $employee->id,
                'employee_name_legacy' => $employee->full_name,
                'area' => ($data['department'] ?? null) ?: ($employee->department ?: $cellphone->area),
                'status' => 'En Uso',
            ]);

            $this->audit->record('assign', 'assignments', $assignment->id, null, $assignment->getAttributes(), $performer);
            $this->audit->record('update', 'cellphones', $cellphone->id, $beforeCellphone, $cellphone->fresh()->getAttributes(), $performer);

            return $assignment;
        });
    }

    /**
     * Process return of an assigned hardware asset with concurrency protection.
     */
    public function returnComputer(int $computerId, int $assignmentId, array $data, User $performer): Assignment
    {
        return DB::transaction(function () use ($computerId, $assignmentId, $data, $performer) {
            $computer = HardwareAsset::whereKey($computerId)->lockForUpdate()->firstOrFail();
            $assignment = Assignment::whereKey($assignmentId)->lockForUpdate()->firstOrFail();

            abort_unless(
                $assignment->asset_type === 'inventory' && (int) $assignment->asset_id === (int) $computer->id,
                404
            );

            if ($assignment->date_returned !== null) {
                throw ValidationException::withMessages([
                    'assignment' => ['La asignacion ya ha sido devuelta previamente.'],
                ]);
            }

            $beforeAssignment = $assignment->getAttributes();
            $assignment->update($data);

            $hasActive = Assignment::where('asset_type', 'inventory')
                ->where('asset_id', $computer->id)
                ->whereNull('date_returned')
                ->exists();

            if (! $hasActive) {
                $beforeComputer = $computer->getAttributes();
                $computer->update([
                    'employee_id' => null,
                    'assigned_user' => null,
                    'status' => 'DISPONIBLE',
                ]);
                $this->audit->record('update', 'hardware_assets', $computer->id, $beforeComputer, $computer->fresh()->getAttributes(), $performer);
            }

            $this->audit->record('return', 'assignments', $assignment->id, $beforeAssignment, $assignment->fresh()->getAttributes(), $performer);

            return $assignment;
        });
    }

    /**
     * Delete an assignment with concurrency protection.
     */
    public function deleteAssignment(int $computerId, int $assignmentId, User $performer): void
    {
        DB::transaction(function () use ($computerId, $assignmentId, $performer) {
            $computer = HardwareAsset::whereKey($computerId)->lockForUpdate()->firstOrFail();
            $assignment = Assignment::whereKey($assignmentId)->lockForUpdate()->firstOrFail();

            abort_unless(
                $assignment->asset_type === 'inventory' && (int) $assignment->asset_id === (int) $computer->id,
                404
            );

            $before = $assignment->getAttributes();
            $wasActive = $assignment->date_returned === null;
            $assignment->delete();

            $this->audit->record('delete', 'assignments', $assignment->id, $before, null, $performer);

            if ($wasActive && ! Assignment::where('asset_type', 'inventory')
                ->where('asset_id', $computer->id)
                ->whereNull('date_returned')
                ->exists()) {
                $beforeComputer = $computer->getAttributes();
                $computer->update([
                    'employee_id' => null,
                    'assigned_user' => null,
                    'delivery_date' => null,
                    'status' => 'DISPONIBLE',
                ]);
                $this->audit->record('update', 'hardware_assets', $computer->id, $beforeComputer, $computer->fresh()->getAttributes(), $performer);
            }
        });
    }
}
