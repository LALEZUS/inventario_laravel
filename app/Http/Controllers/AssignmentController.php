<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Services\AssetAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssignmentController extends Controller
{
    public function store(Request $request, HardwareAsset $computer, AssetAssignmentService $service): RedirectResponse
    {
        $this->authorize('update', $computer);
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'date_assigned' => ['required', 'date'],
            'condition_on_assign' => ['nullable', 'string', 'max:50'],
            'department' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $employee = Employee::findOrFail($data['employee_id']);

        $service->assignComputer($computer->id, $data, $employee, $request->user());

        return redirect()->route('computers.show', $computer)->with('success', 'Asignacion registrada. Ya puedes generar su responsiva.');
    }

    public function return(Request $request, HardwareAsset $computer, Assignment $assignment, AssetAssignmentService $service): RedirectResponse
    {
        $this->authorize('update', $computer);
        $data = $request->validate([
            'date_returned' => ['required', 'date', 'after_or_equal:'.$assignment->date_assigned->format('Y-m-d')],
            'condition_on_return' => ['nullable', 'string', 'max:50'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ]);

        $service->returnComputer($computer->id, $assignment->id, $data, $request->user());

        return back()->with('success', 'Devolucion registrada correctamente.');
    }

    public function destroy(HardwareAsset $computer, Assignment $assignment, AssetAssignmentService $service): RedirectResponse
    {
        abort_unless(request()->user()->role === 'admin', 403);

        $service->deleteAssignment($computer->id, $assignment->id, request()->user());

        return back()->with('success', 'Asignacion eliminada.');
    }
}
