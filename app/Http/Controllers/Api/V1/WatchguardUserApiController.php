<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\WatchguardUserRequest;
use App\Http\Resources\WatchguardUserResource;
use App\Models\AuditLog;
use App\Models\WatchguardUser;
use App\Services\WatchguardUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WatchguardUserApiController extends Controller
{
    public function __construct(
        protected WatchguardUserService $service
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', WatchguardUser::class);

        $search = trim((string) $request->query('search'));
        $perPage = (int) $request->query('per_page', 15);

        $query = WatchguardUser::query()
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(fn ($q) => $q->where('username', 'like', $term)
                    ->orWhere('assigned_to', 'like', $term)
                    ->orWhere('area', 'like', $term)
                    ->orWhere('ip', 'like', $term)
                    ->orWhere('comments', 'like', $term));
            });

        $users = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('username')
        )->paginate($perPage);

        return WatchguardUserResource::collection($users)->additional([
            'meta' => [
                'pagination' => [
                    'total' => $users->total(),
                    'count' => $users->count(),
                    'per_page' => $users->perPage(),
                    'current_page' => $users->currentPage(),
                    'total_pages' => $users->lastPage(),
                ],
            ],
        ]);
    }

    public function show(WatchguardUser $watchguardUser): JsonResponse
    {
        $this->authorize('view', $watchguardUser);

        return response()->json([
            'success' => true,
            'data' => new WatchguardUserResource($watchguardUser),
        ]);
    }

    public function credential(WatchguardUser $watchguardUser): JsonResponse
    {
        $this->authorize('viewSensitive', $watchguardUser);

        return response()->json([
            'success' => true,
            'data' => [
                'password' => $watchguardUser->password ?? '',
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function store(WatchguardUserRequest $request): JsonResponse
    {
        $user = $this->service->store($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Usuario WatchGuard registrado correctamente.',
            'data' => new WatchguardUserResource($user),
        ], 201);
    }

    public function update(WatchguardUserRequest $request, WatchguardUser $watchguardUser): JsonResponse
    {
        $updated = $this->service->update($watchguardUser, $request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Usuario WatchGuard actualizado correctamente.',
            'data' => new WatchguardUserResource($updated),
        ]);
    }

    public function destroy(WatchguardUser $watchguardUser): JsonResponse
    {
        $this->authorize('delete', $watchguardUser);
        $this->service->delete($watchguardUser, request()->user());

        return response()->json([
            'success' => true,
            'message' => 'Usuario WatchGuard eliminado correctamente.',
        ]);
    }

    public function auditLogs(WatchguardUser $watchguardUser): JsonResponse
    {
        $this->authorize('viewAudit', $watchguardUser);

        $logs = AuditLog::where('entity', 'watchguard_users')
            ->where('entity_id', (string) $watchguardUser->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }
}
