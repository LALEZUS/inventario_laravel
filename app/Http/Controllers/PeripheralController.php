<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeripheralRequest;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Models\Peripheral;
use App\Services\PeripheralService;
use App\Support\RecentRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeripheralController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Peripheral::class);
        $search = trim((string) $request->query('search'));
        $query = Peripheral::with(['computer', 'employee'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)->orWhere('serial', 'like', $term)
                        ->orWhere('code', 'like', $term)->orWhere('category', 'like', $term)
                        ->orWhere('assigned_to', 'like', $term)->orWhere('location', 'like', $term)
                        ->orWhereHas('computer', fn ($computer) => $computer->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('employee', fn ($employee) => $employee->where('full_name', 'like', $term));
                });
            });

        $peripherals = RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('name'))
            ->paginate(20)->withQueryString();

        return view('peripherals.index', compact('peripherals', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', Peripheral::class);
        return view('peripherals.create', $this->formData(new Peripheral(['status' => 'Disponible', 'quantity' => 1])));
    }

    public function store(PeripheralRequest $request, PeripheralService $service): RedirectResponse
    {
        $peripheral = $service->store($request->validated());
        return redirect()->route('peripherals.show', $peripheral)->with('success', 'Periferico registrado correctamente.');
    }

    public function show(Peripheral $peripheral): View
    {
        $this->authorize('view', $peripheral);
        $peripheral->load(['computer', 'employee']);
        $files = AssetFile::where('asset_type', 'peripheral')->where('asset_id', $peripheral->id)->latest('uploaded_at')->get();
        $auditLogs = collect();
        if (request()->user()->can('viewAudit', $peripheral)) {
            $auditLogs = AuditLog::where('entity', 'peripherals')->where('entity_id', (string) $peripheral->id)->latest('created_at')->limit(20)->get();
        }
        return view('peripherals.show', compact('peripheral', 'files', 'auditLogs'));
    }

    public function edit(Peripheral $peripheral): View
    {
        $this->authorize('update', $peripheral);
        return view('peripherals.edit', $this->formData($peripheral));
    }

    public function update(PeripheralRequest $request, Peripheral $peripheral, PeripheralService $service): RedirectResponse
    {
        $service->update($peripheral, $request->validated());
        return redirect()->route('peripherals.show', $peripheral)->with('success', 'Periferico actualizado correctamente.');
    }

    public function destroy(Peripheral $peripheral, PeripheralService $service): RedirectResponse
    {
        $this->authorize('delete', $peripheral);
        $service->delete($peripheral);
        return redirect()->route('peripherals.index')->with('success', 'Periferico eliminado correctamente.');
    }

    private function formData(Peripheral $peripheral): array
    {
        return [
            'peripheral' => $peripheral,
            'employees' => Employee::where('status', 'Activo')->orderBy('full_name')->get(['id', 'full_name']),
            'computers' => HardwareAsset::orderBy('name')->get(['id', 'name', 'code', 'assigned_user']),
        ];
    }
}
