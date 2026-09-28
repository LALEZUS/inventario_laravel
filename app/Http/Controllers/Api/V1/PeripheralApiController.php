<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PeripheralRequest;
use App\Http\Resources\PeripheralResource;
use App\Models\Peripheral;
use App\Services\PeripheralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PeripheralApiController extends Controller
{
    public function __construct(
        protected PeripheralService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Peripheral::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $category = trim((string) $request->query('category'));
        $brand = trim((string) $request->query('brand'));
        $perPage = (int) $request->query('per_page', 15);

        $query = Peripheral::with(['computer', 'employee'])
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('name', 'like', $term)
                        ->orWhere('brand', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('serial', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('category', 'like', $term)
                        ->orWhere('assigned_to', 'like', $term)
                        ->orWhere('location', 'like', $term)
                        ->orWhereHas('computer', fn ($c) => $c->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', $term));
                });
            })
            ->when($status !== '' && strtolower($status) !== 'todos', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($category !== '' && strtolower($category) !== 'todas', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($brand !== '', function ($query) use ($brand) {
                $query->where('brand', 'like', '%'.$brand.'%');
            });

        $peripherals = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('name')
        )->paginate($perPage);

        return PeripheralResource::collection($peripherals)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $peripherals->total(),
                    'count' => $peripherals->count(),
                    'per_page' => $peripherals->perPage(),
                    'current_page' => $peripherals->currentPage(),
                    'total_pages' => $peripherals->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Peripheral $peripheral): JsonResponse
    {
        $this->authorize('view', $peripheral);
        $peripheral->load(['computer', 'employee']);

        return response()->json([
            'success' => true,
            'data' => new PeripheralResource($peripheral),
        ]);
    }

    public function store(PeripheralRequest $request): JsonResponse
    {
        $peripheral = $this->service->store($request->validated(), $request->user());
        $peripheral->load(['computer', 'employee']);

        return response()->json([
            'success' => true,
            'message' => 'Periférico registrado correctamente.',
            'data' => new PeripheralResource($peripheral),
        ], 201);
    }

    public function update(PeripheralRequest $request, Peripheral $peripheral): JsonResponse
    {
        $updated = $this->service->update($peripheral, $request->validated(), $request->user());
        $updated->load(['computer', 'employee']);

        return response()->json([
            'success' => true,
            'message' => 'Periférico actualizado correctamente.',
            'data' => new PeripheralResource($updated),
        ]);
    }

    public function destroy(Peripheral $peripheral): JsonResponse
    {
        $this->authorize('delete', $peripheral);
        $this->service->delete($peripheral, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Periférico eliminado correctamente.',
        ]);
    }
}
