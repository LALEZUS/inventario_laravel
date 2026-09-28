<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\AuditLog;
use App\Models\Cellphone;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Models\Printer;
use App\Services\AuditLogger;
use App\Services\EmployeeQrCode;
use App\Support\RecentRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function index(Request $request, EmployeeQrCode $qrCode): View
    {
        $this->authorize('viewAny', Employee::class);
        $search = trim((string) $request->query('search'));
        $query = Employee::withCount(['hardwareAssets', 'cellphones', 'peripherals', 'printers'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($query) => $query->where('full_name', 'like', $term)
                    ->orWhere('department', 'like', $term)->orWhere('position', 'like', $term)
                    ->orWhere('email_corporate', 'like', $term)->orWhere('extension', 'like', $term));
            });

        $employees = RecentRecords::apply($query, $request, fn ($query) => $query
            ->orderByRaw("CASE WHEN status = 'Activo' THEN 0 ELSE 1 END")
            ->orderBy('full_name'))->paginate(20)->withQueryString();

        $qrCodes = $employees->getCollection()->mapWithKeys(
            fn (Employee $employee): array => [$employee->id => $qrCode->dataUri($employee)]
        );

        return view('employees.index', compact('employees', 'search', 'qrCodes'));
    }

    public function create(): View
    {
        $this->authorize('create', Employee::class);
        return view('employees.create', ['employee' => new Employee(['status' => 'Activo'])]);
    }

    public function store(EmployeeRequest $request, AuditLogger $audit): RedirectResponse
    {
        $employee = DB::transaction(function () use ($request, $audit) {
            $employee = Employee::create($request->validated());
            $audit->record('create', 'employees', $employee->id, null, $employee->getAttributes());
            return $employee;
        });
        return redirect()->route('employees.show', $employee)->with('success', 'Empleado registrado correctamente.');
    }

    public function show(Employee $employee): View
    {
        $this->authorize('view', $employee);
        $employee->load([
            'hardwareAssets' => fn ($query) => $query->orderBy('name'),
            'cellphones' => fn ($query) => $query->orderBy('model'),
            'peripherals' => fn ($query) => $query->orderBy('name'),
            'printers' => fn ($query) => $query->orderBy('name'),
            'outlookAccounts' => fn ($query) => $query->orderBy('correo'),
            'assignments.computer',
        ]);
        $auditLogs = collect();
        if (request()->user()->can('viewAudit', $employee)) {
            $auditLogs = AuditLog::where('entity', 'employees')->where('entity_id', (string) $employee->id)->latest('created_at')->limit(20)->get();
        }
        return view('employees.show', compact('employee', 'auditLogs'));
    }

    public function edit(Employee $employee): View
    {
        $this->authorize('update', $employee);
        return view('employees.edit', compact('employee'));
    }

    public function update(EmployeeRequest $request, Employee $employee, AuditLogger $audit): RedirectResponse
    {
        $before = $employee->getAttributes();
        $data = $request->validated();
        DB::transaction(function () use ($employee, $data, $before, $audit) {
            $employee->update($data);
            HardwareAsset::where('employee_id', $employee->id)->update(['assigned_user' => $employee->full_name]);
            Cellphone::where('employee_id', $employee->id)->update(['employee_name_legacy' => $employee->full_name]);
            Peripheral::where('employee_id', $employee->id)->update(['assigned_to' => $employee->full_name]);
            Printer::where('employee_id', $employee->id)->update(['assigned_to' => $employee->full_name]);
            $audit->record('update', 'employees', $employee->id, $before, $employee->fresh()->getAttributes());
        });
        return redirect()->route('employees.show', $employee)->with('success', 'Empleado y relaciones actualizados correctamente.');
    }

    public function destroy(Employee $employee, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $employee);
        $before = $employee->getAttributes();
        DB::transaction(function () use ($employee, $before, $audit) {
            HardwareAsset::where('employee_id', $employee->id)->update(['assigned_user' => $employee->full_name]);
            Cellphone::where('employee_id', $employee->id)->update(['employee_name_legacy' => $employee->full_name]);
            Peripheral::where('employee_id', $employee->id)->update(['assigned_to' => $employee->full_name]);
            Printer::where('employee_id', $employee->id)->update(['assigned_to' => $employee->full_name]);
            $employee->delete();
            $audit->record('delete', 'employees', $employee->id, $before, null);
        });
        return redirect()->route('employees.index')->with('success', 'Empleado eliminado; sus activos conservaron el nombre historico.');
    }
}
