<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrinterRequest;
use App\Http\Resources\PrinterResource;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Services\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PrinterApiController extends Controller
{
    public function __construct(
        protected PrinterService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Printer::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $network = $request->query('network');
        $perPage = (int) $request->query('per_page', 15);

        $query = Printer::with('employee')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('serial', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('ip_address', 'like', $term)
                        ->orWhere('zone', 'like', $term)
                        ->orWhere('assigned_to', 'like', $term)
                        ->orWhere('ink_type', 'like', $term)
                        ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', $term));
                });
            })
            ->when($status !== '' && strtolower($status) !== 'todos', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(in_array($network, ['0', '1'], true), function ($query) use ($network) {
                $query->where('is_network', $network === '1');
            });

        $printers = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('name')
        )->paginate($perPage);

        return PrinterResource::collection($printers)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $printers->total(),
                    'count' => $printers->count(),
                    'per_page' => $printers->perPage(),
                    'current_page' => $printers->currentPage(),
                    'total_pages' => $printers->lastPage(),
                ],
            ],
        ]);
    }

    public function options(): JsonResponse
    {
        $this->authorize('viewAny', Printer::class);

        $employees = Employee::where('status', 'Activo')
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'department']);

        $inks = Ink::orderBy('brand')->orderBy('type')->orderBy('color')->get(['id', 'brand', 'model', 'color', 'type', 'capacity', 'status', 'quantity']);
        $toners = Toner::orderBy('brand')->orderBy('model')->get(['id', 'brand', 'model', 'status', 'quantity']);

        return response()->json([
            'success' => true,
            'data' => [
                'employees' => $employees,
                'inks' => $inks,
                'toners' => $toners,
                'status_options' => ['Activo', 'Disponible', 'En servicio', 'Mantenimiento', 'Baja'],
                'supply_type_options' => ['Tinta', 'Tóner', 'Mixto', 'Otro'],
            ],
        ]);
    }

    public function show(Printer $printer): JsonResponse
    {
        $this->authorize('view', $printer);
        $printer->load('employee');

        $inks = Ink::whereIn('id', $printer->linkedInkIds())->get(['id', 'brand', 'model', 'color', 'type', 'capacity', 'status', 'quantity']);
        $toners = Toner::whereIn('id', $printer->linkedTonerIds())->get(['id', 'brand', 'model', 'status', 'quantity']);

        $resourceData = (new PrinterResource($printer))->resolve(request());
        $resourceData['linked_inks_detail'] = $inks;
        $resourceData['linked_toner_detail'] = $toners;

        return response()->json([
            'success' => true,
            'data' => $resourceData,
        ]);
    }

    public function consumables(Printer $printer): JsonResponse
    {
        $this->authorize('view', $printer);

        $inks = Ink::whereIn('id', $printer->linkedInkIds())->get(['id', 'brand', 'model', 'color', 'type', 'capacity', 'status', 'quantity']);
        $toners = Toner::whereIn('id', $printer->linkedTonerIds())->get(['id', 'brand', 'model', 'status', 'quantity']);

        return response()->json([
            'success' => true,
            'data' => [
                'printer_id' => $printer->id,
                'printer_name' => $printer->name,
                'inks' => $inks,
                'toners' => $toners,
            ],
        ]);
    }

    public function files(Printer $printer): JsonResponse
    {
        $this->authorize('view', $printer);

        $files = AssetFile::where('asset_type', 'printer')
            ->where('asset_id', $printer->id)
            ->latest('uploaded_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $files->map(fn ($file) => [
                'id' => $file->id,
                'original_name' => $file->original_name,
                'label' => $file->label,
                'file_size' => $file->file_size,
                'file_size_formatted' => $file->file_size ? number_format($file->file_size / 1024, 2).' KB' : 'N/A',
                'uploaded_at' => $file->uploaded_at?->toIso8601String(),
                'url' => route('api.v1.files.preview', $file),
                'download_url' => route('api.v1.files.download', $file),
            ]),
        ]);
    }

    public function auditLogs(Printer $printer): JsonResponse
    {
        $this->authorize('viewAudit', $printer);

        $logs = AuditLog::where('entity', 'printers')
            ->where('entity_id', (string) $printer->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function store(PrinterRequest $request): JsonResponse
    {
        $printer = $this->service->store($request->validated(), $request->user());
        $printer->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Impresora registrada correctamente.',
            'data' => new PrinterResource($printer),
        ], 201);
    }

    public function update(PrinterRequest $request, Printer $printer): JsonResponse
    {
        $updated = $this->service->update($printer, $request->validated(), $request->user());
        $updated->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Impresora actualizada correctamente.',
            'data' => new PrinterResource($updated),
        ]);
    }

    public function destroy(Printer $printer): JsonResponse
    {
        $this->authorize('delete', $printer);
        $this->service->delete($printer, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Impresora eliminada correctamente.',
        ]);
    }
}
