<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\NetworkDeviceRequest;
use App\Http\Resources\NetworkDeviceResource;
use App\Models\AuditLog;
use App\Models\EnterpriseNetwork;
use App\Models\NetworkDevice;
use App\Models\WatchguardUser;
use App\Services\NetworkDeviceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NetworkDeviceApiController extends Controller
{
    public function __construct(
        protected NetworkDeviceService $service
    ) {}

    public function stats(): JsonResponse
    {
        $this->authorize('viewAny', NetworkDevice::class);

        return response()->json([
            'success' => true,
            'data' => [
                'devices_count' => NetworkDevice::count(),
                'active_devices_count' => NetworkDevice::where('status', 'Activo')->count(),
                'watchguard_count' => WatchguardUser::count(),
                'networks_count' => EnterpriseNetwork::count(),
            ],
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', NetworkDevice::class);

        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $deviceType = trim((string) $request->query('device_type'));
        $perPage = (int) $request->query('per_page', 15);

        $query = NetworkDevice::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q->where('device_name', 'like', $term)
                    ->orWhere('device_type', 'like', $term)
                    ->orWhere('ip_address', 'like', $term)
                    ->orWhere('mac_address', 'like', $term)
                    ->orWhere('location', 'like', $term)
                    ->orWhere('brand', 'like', $term)
                    ->orWhere('comments', 'like', $term));
            })
            ->when($status !== '' && strtolower($status) !== 'todos', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($deviceType !== '' && strtolower($deviceType) !== 'todos', function ($query) use ($deviceType) {
                $query->where('device_type', $deviceType);
            });

        $devices = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('device_name')
        )->paginate($perPage);

        return NetworkDeviceResource::collection($devices)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $devices->total(),
                    'count' => $devices->count(),
                    'per_page' => $devices->perPage(),
                    'current_page' => $devices->currentPage(),
                    'total_pages' => $devices->lastPage(),
                ],
            ],
        ]);
    }

    public function show(NetworkDevice $networkDevice): JsonResponse
    {
        $this->authorize('view', $networkDevice);

        return response()->json([
            'success' => true,
            'data' => new NetworkDeviceResource($networkDevice),
        ]);
    }

    public function store(NetworkDeviceRequest $request): JsonResponse
    {
        $device = $this->service->store($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo de red registrado correctamente.',
            'data' => new NetworkDeviceResource($device),
        ], 201);
    }

    public function update(NetworkDeviceRequest $request, NetworkDevice $networkDevice): JsonResponse
    {
        $updated = $this->service->update($networkDevice, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo de red actualizado correctamente.',
            'data' => new NetworkDeviceResource($updated),
        ]);
    }

    public function destroy(NetworkDevice $networkDevice): JsonResponse
    {
        $this->authorize('delete', $networkDevice);
        $this->service->delete($networkDevice, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo de red eliminado correctamente.',
        ]);
    }

    public function auditLogs(NetworkDevice $networkDevice): JsonResponse
    {
        $this->authorize('viewAudit', $networkDevice);

        $logs = AuditLog::where('entity', 'network_devices')
            ->where('entity_id', (string) $networkDevice->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
