<?php

namespace App\Http\Controllers;

use App\Http\Requests\HardwareAssetRequest;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\HardwareAsset;
use App\Services\HardwareAssetService;
use App\Services\AuditLogger;
use App\Services\NfoParser;
use App\Support\RecentRecords;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class HardwareAssetController extends Controller
{
    private const BOOLEAN_FIELDS = ['has_office', 'has_winrar', 'has_reader', 'has_server', 'has_printer'];

    public function index(Request $request): View
    {
        $this->authorize('viewAny', HardwareAsset::class);
        $search = trim((string) $request->query('search'));

        $query = HardwareAsset::with('employee')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($query) use ($term) {
                    $query->where('name', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('serial', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('assigned_user', 'like', $term)
                        ->orWhere('zone', 'like', $term)
                        ->orWhere('processor', 'like', $term)
                        ->orWhere('anydesk_id', 'like', $term)
                        ->orWhere('rustdesk_id', 'like', $term)
                        ->orWhere('value', 'like', $term)
                        ->orWhereHas('employee', fn ($employee) => $employee->where('full_name', 'like', $term));
                });
            });

        $computers = RecentRecords::apply($query, $request, fn ($query) => $query->orderBy('name'))
            ->paginate(20)
            ->withQueryString();

        return view('computers.index', compact('computers', 'search'));
    }

    public function create(): View
    {
        $this->authorize('create', HardwareAsset::class);

        return view('computers.create', [
            'computer' => new HardwareAsset(['status' => 'DISPONIBLE', 'category' => 'Equipo']),
            'employees' => $this->employees(),
        ]);
    }

    public function store(
        HardwareAssetRequest $request,
        HardwareAssetService $service,
    ): RedirectResponse {
        $computer = $service->store($request->validated(), $request->file('nfo_file'), $request->user());

        return redirect()->route('computers.show', $computer)
            ->with('success', 'Computadora registrada correctamente.');
    }

    public function show(HardwareAsset $computer, AuditLogger $audit): View
    {
        $this->authorize('view', $computer);
        if (request()->query('source') === 'qr') {
            $sessionKey = 'qr_scan_'.$computer->id;
            $lastScan = (int) request()->session()->get($sessionKey, 0);
            if ($lastScan < now()->subMinutes(5)->timestamp) {
                $audit->record('qr_scan', 'hardware_assets', $computer->id, null, ['url' => request()->fullUrl()]);
                request()->session()->put($sessionKey, now()->timestamp);
            }
        }
        $computer->load(['employee', 'peripherals', 'assignments.employee', 'assignments.responsivas', 'maintenanceLogs']);
        $files = AssetFile::where('asset_type', 'inventory')
            ->where('asset_id', $computer->id)
            ->latest('uploaded_at')
            ->get();
        $auditLogs = collect();

        if (request()->user()->can('viewAudit', HardwareAsset::class)) {
            $auditLogs = AuditLog::query()
                ->where('entity', 'hardware_assets')
                ->where('entity_id', (string) $computer->id)
                ->latest('created_at')
                ->limit(20)
                ->get();
        }

        return view('computers.show', [
            'computer' => $computer,
            'files' => $files,
            'auditLogs' => $auditLogs,
            'employees' => $this->employees(),
        ]);
    }

    public function edit(HardwareAsset $computer): View
    {
        $this->authorize('update', $computer);

        return view('computers.edit', [
            'computer' => $computer,
            'employees' => $this->employees(),
        ]);
    }

    public function update(
        HardwareAssetRequest $request,
        HardwareAsset $computer,
        HardwareAssetService $service,
    ): RedirectResponse {
        $service->update($computer->id, $request->validated(), $request->file('nfo_file'), $request->user());

        return redirect()->route('computers.show', $computer)
            ->with('success', 'Computadora actualizada correctamente.');
    }

    public function destroy(HardwareAsset $computer, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $computer);
        $before = $computer->getAttributes();
        $nfoPath = $computer->nfo_file;

        DB::transaction(function () use ($computer, $before, $audit) {
            $computer->delete();
            $audit->record('delete', 'hardware_assets', $computer->id, $before, null);
        });

        if ($nfoPath && str_starts_with($nfoPath, 'nfo/')) {
            Storage::disk('local')->delete($nfoPath);
        }

        return redirect()->route('computers.index')
            ->with('success', 'Computadora eliminada correctamente.');
    }

    public function parseNfo(Request $request, NfoParser $parser): JsonResponse
    {
        $this->authorize('upload', HardwareAsset::class);
        $request->validate([
            'nfo_file' => [
                'required',
                'file',
                'max:10240',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (strtolower($value->getClientOriginalExtension()) !== 'nfo') {
                        $fail('El archivo debe tener extension .nfo.');
                    }
                },
            ],
        ]);

        $specifications = $parser->parseFile($request->file('nfo_file')->getRealPath());

        return response()->json([
            'specifications' => $specifications,
            'count' => count($specifications),
            'message' => $specifications === []
                ? 'No se encontraron especificaciones reconocibles.'
                : 'Especificaciones detectadas correctamente.',
        ]);
    }

    public function downloadNfo(HardwareAsset $computer): BinaryFileResponse
    {
        $this->authorize('view', $computer);

        abort_if(blank($computer->nfo_file), 404);

        if (str_starts_with($computer->nfo_file, 'nfo/') && Storage::disk('local')->exists($computer->nfo_file)) {
            return response()->download(
                Storage::disk('local')->path($computer->nfo_file),
                basename($computer->nfo_file),
                ['Content-Type' => 'application/octet-stream'],
            );
        }

        $legacyPath = rtrim((string) config('inventory.legacy_files_root'), '/\\')
            .DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'nfo'.DIRECTORY_SEPARATOR.basename($computer->nfo_file);
        abort_unless(is_file($legacyPath), 404);

        return response()->download($legacyPath, basename($legacyPath), ['Content-Type' => 'application/octet-stream']);
    }

    private function prepareData(
        HardwareAssetRequest $request,
        NfoParser $parser,
        ?HardwareAsset $computer = null,
    ): array {
        $data = $request->safe()->except('nfo_file');

        foreach (self::BOOLEAN_FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        if ($request->hasFile('nfo_file')) {
            $this->authorize('upload', HardwareAsset::class);
            $specifications = $parser->parseFile($request->file('nfo_file')->getRealPath());

            foreach ($specifications as $field => $value) {
                if (blank($data[$field] ?? null)) {
                    $data[$field] = $value;
                }
            }

            $original = pathinfo($request->file('nfo_file')->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = Str::slug($original) ?: 'equipo';
            $fileName = now()->format('YmdHis').'-'.Str::uuid().'-'.$safeName.'.nfo';
            $data['nfo_file'] = $request->file('nfo_file')->storeAs('nfo', $fileName, 'local');
        } elseif ($computer) {
            $data['nfo_file'] = $computer->nfo_file;
        }

        if (blank($data['name'] ?? null)) {
            throw ValidationException::withMessages([
                'name' => 'Escribe el nombre del equipo o carga un NFO que contenga el modelo.',
            ]);
        }

        if (filled($data['employee_id'] ?? null)) {
            $data['assigned_user'] = Employee::findOrFail($data['employee_id'])->full_name;
        }

        if ($computer && blank($data['admin_password'] ?? null)) {
            $data['admin_password'] = $computer->admin_password;
        }

        $data['category'] = ($data['category'] ?? null) ?: 'Equipo';

        return $data;
    }

    private function employees()
    {
        return Employee::query()
            ->orderByRaw("CASE WHEN status = 'Activo' THEN 0 ELSE 1 END")
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'department', 'status']);
    }
}
