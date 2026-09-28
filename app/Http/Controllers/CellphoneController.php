<?php

namespace App\Http\Controllers;

use App\Http\Requests\CellphoneRequest;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Cellphone;
use App\Models\Employee;
use App\Services\AuditLogger;
use App\Support\RecentRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Services\CellphoneService;

class CellphoneController extends Controller
{
    private const SECRET_FIELDS = ['password', 'updated_password', 'app_lock_password', 'app_lock_answer', 'app_lock_pattern'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Cellphone::class);
        $search = trim((string) $request->query('search'));
        $query = Cellphone::with('employee')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('employee_name_legacy', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('area', 'like', $term)
                        ->orWhere('email_account', 'like', $term)
                        ->orWhere('phone_number', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhereHas('employee', fn ($employee) => $employee->where('full_name', 'like', $term));
                });
            });

        $cellphones = RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('employee_name_legacy'))
            ->paginate(20)
            ->withQueryString();

        return view('cellphones.index', compact('cellphones', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Cellphone::class);
        return view('cellphones.create', [
            'cellphone' => new Cellphone(['status' => 'En Uso']),
            'employees' => $this->employees(),
        ]);
    }

    public function store(CellphoneRequest $request, CellphoneService $service): RedirectResponse
    {
        $cellphone = $service->store($request->validated(), $request->boolean('has_app_lock'), $request->user());

        return redirect()->route('cellphones.show', $cellphone)->with('success', 'Celular registrado correctamente.');
    }

    public function show(Cellphone $cellphone): View
    {
        $this->authorize('view', $cellphone);
        $cellphone->load(['employee', 'assignments.employee']);
        $employees = $this->employees();
        $files = AssetFile::where('asset_type', 'cellphone')->where('asset_id', $cellphone->id)->latest('uploaded_at')->get();
        $auditLogs = collect();
        if (request()->user()->can('viewAudit', $cellphone)) {
            $auditLogs = AuditLog::where('entity', 'cellphones')->where('entity_id', (string) $cellphone->id)->latest('created_at')->limit(20)->get();
        }

        return view('cellphones.show', compact('cellphone', 'files', 'auditLogs', 'employees'));
    }

    public function edit(Cellphone $cellphone): View
    {
        $this->authorize('update', $cellphone);
        return view('cellphones.edit', ['cellphone' => $cellphone, 'employees' => $this->employees()]);
    }

    public function update(CellphoneRequest $request, Cellphone $cellphone, CellphoneService $service): RedirectResponse
    {
        $service->update($cellphone->id, $request->validated(), $request->boolean('has_app_lock'), $request->user());

        return redirect()->route('cellphones.show', $cellphone)->with('success', 'Celular actualizado correctamente.');
    }

    public function destroy(Cellphone $cellphone, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $cellphone);
        $before = $cellphone->getAttributes();
        DB::transaction(function () use ($cellphone, $before, $audit) {
            $cellphone->delete();
            $audit->record('delete', 'cellphones', $cellphone->id, $before, null);
        });

        return redirect()->route('cellphones.index')->with('success', 'Celular eliminado correctamente.');
    }

    public function share(Cellphone $cellphone): RedirectResponse
    {
        $this->authorize('shareSensitive', $cellphone);
        $cellphone->load('employee');
        $fields = [
            'Celular' => $cellphone->model,
            'Empleado' => $cellphone->assigned_name,
            'Area' => $cellphone->area,
            'Telefono' => $cellphone->phone_number,
            'Correo' => $cellphone->email_account,
            'Cuenta de recuperacion' => $cellphone->recovery_account,
            'Contrasena' => $cellphone->password,
            'Contrasena actualizada' => $cellphone->updated_password,
            'Bloqueo de aplicaciones / PIN' => $cellphone->app_lock_password,
            'Respuesta de seguridad' => $cellphone->app_lock_answer,
            'Patron' => $cellphone->app_lock_pattern,
            'Estado' => $cellphone->status,
        ];
        $lines = [];
        foreach ($fields as $label => $value) {
            if (filled($value)) $lines[] = '*'.$label.':* '.trim((string) $value);
        }
        $lines[] = '';
        $lines[] = 'Compartido desde Inventario Total Ground.';

        return redirect()->away('https://wa.me/?text='.rawurlencode(implode("\n", $lines)));
    }

    private function prepareData(CellphoneRequest $request, ?Cellphone $cellphone = null): array
    {
        $data = $request->validated();
        $data['has_app_lock'] = $request->boolean('has_app_lock');
        foreach (self::SECRET_FIELDS as $field) {
            if ($cellphone && blank($data[$field] ?? null)) $data[$field] = $cellphone->{$field};
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

    private function employees()
    {
        return Employee::where('status', 'Activo')->orderBy('full_name')->get(['id', 'full_name', 'department']);
    }
}
