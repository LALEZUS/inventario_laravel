<?php

namespace App\Http\Controllers;

use App\Http\Requests\PrinterRequest;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Services\PrinterService;
use App\Support\RecentRecords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrinterController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Printer::class);
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $network = $request->query('network');

        $query = Printer::with('employee')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('serial', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhere('zone', 'like', $term)
                        ->orWhere('assigned_to', 'like', $term)
                        ->orWhere('ink_type', 'like', $term)
                        ->orWhereHas('employee', fn ($employee) => $employee->where('full_name', 'like', $term));
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when(in_array($network, ['0', '1'], true), fn ($query) => $query->where('is_network', $network === '1'));

        $printers = RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('name'))
            ->paginate(20)
            ->withQueryString();

        return view('printers.index', [
            'printers' => $printers,
            'search' => $search,
            'status' => $status,
            'network' => $network,
            'stats' => [
                'total' => Printer::count(),
                'network' => Printer::where('is_network', true)->count(),
                'maintenance' => Printer::where('status', 'Mantenimiento')->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Printer::class);

        return view('printers.create', $this->formData(new Printer(['status' => 'Activo'])));
    }

    public function store(PrinterRequest $request, PrinterService $service): RedirectResponse
    {
        $printer = $service->store($request->validated());

        return redirect()->route('printers.show', $printer)->with('success', 'Impresora registrada correctamente.');
    }

    public function show(Printer $printer): View
    {
        $this->authorize('view', $printer);
        $printer->load('employee');
        $inks = Ink::whereIn('id', $printer->linkedInkIds())->orderBy('type')->orderBy('color')->get();
        $toners = Toner::whereIn('id', $printer->linkedTonerIds())->orderBy('brand')->orderBy('model')->get();
        $files = AssetFile::where('asset_type', 'printer')->where('asset_id', $printer->id)->latest('uploaded_at')->get();
        $auditLogs = collect();
        if (request()->user()->can('viewAudit', $printer)) {
            $auditLogs = AuditLog::where('entity', 'printers')->where('entity_id', (string) $printer->id)
                ->latest('created_at')->limit(20)->get();
        }

        return view('printers.show', compact('printer', 'inks', 'toners', 'files', 'auditLogs'));
    }

    public function edit(Printer $printer): View
    {
        $this->authorize('update', $printer);

        return view('printers.edit', $this->formData($printer));
    }

    public function update(PrinterRequest $request, Printer $printer, PrinterService $service): RedirectResponse
    {
        $service->update($printer, $request->validated());

        return redirect()->route('printers.show', $printer)->with('success', 'Impresora actualizada correctamente.');
    }

    public function destroy(Printer $printer, PrinterService $service): RedirectResponse
    {
        $this->authorize('delete', $printer);
        $service->delete($printer);

        return redirect()->route('printers.index')->with('success', 'Impresora eliminada correctamente.');
    }

    private function formData(Printer $printer): array
    {
        return [
            'printer' => $printer,
            'employees' => Employee::where('status', 'Activo')->orderBy('full_name')->get(['id', 'full_name']),
            'inks' => Ink::orderBy('brand')->orderBy('type')->orderBy('color')->get(),
            'toners' => Toner::orderBy('brand')->orderBy('model')->get(),
        ];
    }
}
