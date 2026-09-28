<?php

namespace App\Http\Controllers;

use App\Models\Cellphone;
use App\Models\Employee;
use App\Services\AssetAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CellphoneAssignmentController extends Controller
{
    public function store(Request $request, Cellphone $cellphone, AssetAssignmentService $service): RedirectResponse
    {
        $this->authorize('update', $cellphone);

        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date_assigned' => ['required', 'date'],
            'condition_on_assign' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::where('status', 'Activo')->findOrFail($data['employee_id']);
        $service->reassignCellphone($cellphone->id, $data, $employee, $request->user());

        return redirect()
            ->route('cellphones.show', $cellphone)
            ->with('success', 'Nueva asignacion registrada. El usuario anterior quedo guardado en el historial.');
    }
}
