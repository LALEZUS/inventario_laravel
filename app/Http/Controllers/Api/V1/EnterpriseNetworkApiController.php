<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EnterpriseNetworkRequest;
use App\Http\Resources\EnterpriseNetworkResource;
use App\Models\AuditLog;
use App\Models\EnterpriseNetwork;
use App\Services\EnterpriseNetworkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EnterpriseNetworkApiController extends Controller
{
    public function __construct(
        protected EnterpriseNetworkService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', EnterpriseNetwork::class);

        $search = trim((string) $request->query('search'));
        $perPage = (int) $request->query('per_page', 15);

        $query = EnterpriseNetwork::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q->where('network_name', 'like', $term)
                    ->orWhere('vlan', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('encryption', 'like', $term)
                    ->orWhere('comments', 'like', $term)
                    ->orWhere('notes_extra', 'like', $term));
            });

        $networks = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('network_name')
        )->paginate($perPage);

        return EnterpriseNetworkResource::collection($networks)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $networks->total(),
                    'count' => $networks->count(),
                    'per_page' => $networks->perPage(),
                    'current_page' => $networks->currentPage(),
                    'total_pages' => $networks->lastPage(),
                ],
            ],
        ]);
    }

    public function show(EnterpriseNetwork $enterpriseNetwork): JsonResponse
    {
        $this->authorize('view', $enterpriseNetwork);

        return response()->json([
            'success' => true,
            'data' => new EnterpriseNetworkResource($enterpriseNetwork),
        ]);
    }

    public function credential(EnterpriseNetwork $enterpriseNetwork): JsonResponse
    {
        $this->authorize('viewSensitive', $enterpriseNetwork);

        return response()->json([
            'success' => true,
            'data' => [
                'password' => $enterpriseNetwork->password ?? '',
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function store(EnterpriseNetworkRequest $request): JsonResponse
    {
        $network = $this->service->store($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Red empresarial registrada correctamente.',
            'data' => new EnterpriseNetworkResource($network),
        ], 201);
    }

    public function update(EnterpriseNetworkRequest $request, EnterpriseNetwork $enterpriseNetwork): JsonResponse
    {
        $updated = $this->service->update($enterpriseNetwork, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Red empresarial actualizada correctamente.',
            'data' => new EnterpriseNetworkResource($updated),
        ]);
    }

    public function destroy(EnterpriseNetwork $enterpriseNetwork): JsonResponse
    {
        $this->authorize('delete', $enterpriseNetwork);
        $this->service->delete($enterpriseNetwork, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Red empresarial eliminada correctamente.',
        ]);
    }

    public function auditLogs(EnterpriseNetwork $enterpriseNetwork): JsonResponse
    {
        $this->authorize('viewAudit', $enterpriseNetwork);

        $logs = AuditLog::where('entity', 'enterprise_networks')
            ->where('entity_id', (string) $enterpriseNetwork->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
