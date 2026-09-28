<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InkRequest;
use App\Http\Requests\TonerRequest;
use App\Http\Resources\InkResource;
use App\Http\Resources\TonerResource;
use App\Models\AuditLog;
use App\Models\Ink;
use App\Models\Printer;
use App\Models\Toner;
use App\Services\InkService;
use App\Services\TonerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConsumableApiController extends Controller
{
    public function __construct(
        protected InkService $inkService,
        protected TonerService $tonerService
    ) {}

    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', Ink::class);
        $this->authorize('viewAny', Toner::class);

        return response()->json([
            'success' => true,
            'data' => [
                'ink_types' => Ink::count(),
                'ink_units' => (int) Ink::sum('quantity'),
                'toner_types' => Toner::count(),
                'toner_units' => (int) Toner::sum('quantity'),
                'low_stock' => Ink::whereColumn('quantity', '<=', 'low_stock_threshold')->count()
                    + Toner::whereColumn('quantity', '<=', 'low_stock_threshold')->count(),
            ],
        ]);
    }

    // --- TINTAS (INKS) ---

    public function inksIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Ink::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $perPage = (int) $request->query('per_page', 15);

        $query = Ink::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q->where('brand', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('color', 'like', $term)
                    ->orWhere('type', 'like', $term)
                    ->orWhere('capacity', 'like', $term)
                    ->orWhere('comments', 'like', $term));
            })
            ->when($status !== '' && strtolower($status) !== 'todos', function ($query) use ($status) {
                $query->where('status', $status);
            });

        $inks = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('brand')->orderBy('type')->orderBy('color')
        )->paginate($perPage);

        return InkResource::collection($inks)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $inks->total(),
                    'count' => $inks->count(),
                    'per_page' => $inks->perPage(),
                    'current_page' => $inks->currentPage(),
                    'total_pages' => $inks->lastPage(),
                ],
            ],
        ]);
    }

    public function inksShow(Ink $ink): JsonResponse
    {
        $this->authorize('view', $ink);

        $printers = Printer::all()
            ->filter(fn (Printer $p) => in_array($ink->id, $p->linkedInkIds(), true))
            ->values()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand,
                'model' => $p->model,
                'code' => $p->code,
                'ip_address' => $p->ip_address,
                'status' => $p->status,
            ]);

        $resourceData = (new InkResource($ink))->resolve(request());
        $resourceData['compatible_printers'] = $printers;

        return response()->json([
            'success' => true,
            'data' => $resourceData,
        ]);
    }

    public function inksPrinters(Ink $ink): JsonResponse
    {
        $this->authorize('view', $ink);

        $printers = Printer::all()
            ->filter(fn (Printer $p) => in_array($ink->id, $p->linkedInkIds(), true))
            ->values()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand,
                'model' => $p->model,
                'code' => $p->code,
                'ip_address' => $p->ip_address,
                'status' => $p->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => $printers,
        ]);
    }

    public function inksAudit(Ink $ink): JsonResponse
    {
        $this->authorize('viewAudit', $ink);

        $logs = AuditLog::where('entity', 'inks')
            ->where('entity_id', (string) $ink->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function inksStore(InkRequest $request): JsonResponse
    {
        $ink = $this->inkService->store($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tinta registrada correctamente.',
            'data' => new InkResource($ink),
        ], 201);
    }

    public function inksUpdate(InkRequest $request, Ink $ink): JsonResponse
    {
        $updated = $this->inkService->update($ink, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tinta actualizada correctamente.',
            'data' => new InkResource($updated),
        ]);
    }

    public function inksDestroy(Ink $ink): JsonResponse
    {
        $this->authorize('delete', $ink);
        $this->inkService->delete($ink, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Tinta eliminada correctamente.',
        ]);
    }

    // --- TÓNER (TONER) ---

    public function tonerIndex(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Toner::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $perPage = (int) $request->query('per_page', 15);

        $query = Toner::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q->where('brand', 'like', $term)
                    ->orWhere('model', 'like', $term)
                    ->orWhere('comentarios', 'like', $term)
                    ->orWhere('comments', 'like', $term));
            })
            ->when($status !== '' && strtolower($status) !== 'todos', function ($query) use ($status) {
                $query->where('status', $status);
            });

        $toners = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('brand')->orderBy('model')
        )->paginate($perPage);

        return TonerResource::collection($toners)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $toners->total(),
                    'count' => $toners->count(),
                    'per_page' => $toners->perPage(),
                    'current_page' => $toners->currentPage(),
                    'total_pages' => $toners->lastPage(),
                ],
            ],
        ]);
    }

    public function tonerShow(Toner $toner): JsonResponse
    {
        $this->authorize('view', $toner);

        $printers = Printer::all()
            ->filter(fn (Printer $p) => in_array($toner->id, $p->linkedTonerIds(), true))
            ->values()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand,
                'model' => $p->model,
                'code' => $p->code,
                'ip_address' => $p->ip_address,
                'status' => $p->status,
            ]);

        $resourceData = (new TonerResource($toner))->resolve(request());
        $resourceData['compatible_printers'] = $printers;

        return response()->json([
            'success' => true,
            'data' => $resourceData,
        ]);
    }

    public function tonerPrinters(Toner $toner): JsonResponse
    {
        $this->authorize('view', $toner);

        $printers = Printer::all()
            ->filter(fn (Printer $p) => in_array($toner->id, $p->linkedTonerIds(), true))
            ->values()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'brand' => $p->brand,
                'model' => $p->model,
                'code' => $p->code,
                'ip_address' => $p->ip_address,
                'status' => $p->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => $printers,
        ]);
    }

    public function tonerAudit(Toner $toner): JsonResponse
    {
        $this->authorize('viewAudit', $toner);

        $logs = AuditLog::where('entity', 'toner')
            ->where('entity_id', (string) $toner->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    public function tonerStore(TonerRequest $request): JsonResponse
    {
        $toner = $this->tonerService->store($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tóner registrado correctamente.',
            'data' => new TonerResource($toner),
        ], 201);
    }

    public function tonerUpdate(TonerRequest $request, Toner $toner): JsonResponse
    {
        $updated = $this->tonerService->update($toner, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Tóner actualizado correctamente.',
            'data' => new TonerResource($updated),
        ]);
    }

    public function tonerDestroy(Toner $toner): JsonResponse
    {
        $this->authorize('delete', $toner);
        $this->tonerService->delete($toner, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Tóner eliminado correctamente.',
        ]);
    }
}
