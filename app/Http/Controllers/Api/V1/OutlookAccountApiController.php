<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\OutlookAccountApiRequest;
use App\Http\Resources\OutlookAccountResource;
use App\Models\OutlookAccount;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OutlookAccountApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', OutlookAccount::class);

        $query = OutlookAccount::with('employee');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('correo', 'like', "%{$search}%")
                    ->orWhere('estatus', 'like', "%{$search}%")
                    ->orWhere('comentarios', 'like', "%{$search}%")
                    ->orWhere('servidor_entrada', 'like', "%{$search}%")
                    ->orWhere('servidor_salida', 'like', "%{$search}%")
                    ->orWhereHas('employee', fn ($emp) => $emp->where('full_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('estatus')) {
            $query->where('estatus', $request->input('estatus'));
        }

        $items = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->latest('updated_at')
        )->paginate(20);

        return response()->json([
            'success' => true,
            'data' => OutlookAccountResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(OutlookAccount $correos_outlook): JsonResponse
    {
        $this->authorize('view', $correos_outlook);
        $correos_outlook->load('employee');

        return response()->json([
            'success' => true,
            'data' => new OutlookAccountResource($correos_outlook, true),
        ]);
    }

    public function store(OutlookAccountApiRequest $request, AuditLogger $logger): JsonResponse
    {
        $this->authorize('create', OutlookAccount::class);
        $data = $this->normalizeData($request->validated());

        $account = DB::transaction(function () use ($data, $logger) {
            $model = OutlookAccount::create($data);
            $logger->record('create', 'correos_outlook', $model->id, null, $model->getAttributes());
            return $model;
        });

        return response()->json([
            'success' => true,
            'message' => 'Correo Outlook registrado correctamente.',
            'data' => new OutlookAccountResource($account->fresh()->load('employee'), true),
        ], 201);
    }

    public function update(OutlookAccountApiRequest $request, OutlookAccount $correos_outlook, AuditLogger $logger): JsonResponse
    {
        $this->authorize('update', $correos_outlook);
        $before = $correos_outlook->getAttributes();
        $validated = $request->validated();
        $data = $this->normalizeData($validated);

        if (blank($this->passwordFrom($validated))) {
            unset($data['contraseña']);
        }

        DB::transaction(function () use ($correos_outlook, $data, $before, $logger) {
            $correos_outlook->update($data);
            $logger->record('update', 'correos_outlook', $correos_outlook->id, $before, $correos_outlook->fresh()->getAttributes());
        });

        return response()->json([
            'success' => true,
            'message' => 'Correo Outlook actualizado correctamente.',
            'data' => new OutlookAccountResource($correos_outlook->fresh()->load('employee'), true),
        ]);
    }

    public function destroy(OutlookAccount $correos_outlook, AuditLogger $logger): JsonResponse
    {
        $this->authorize('delete', $correos_outlook);
        $before = $correos_outlook->getAttributes();
        $correos_outlook->delete();
        $logger->record('delete', 'correos_outlook', $correos_outlook->id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Correo Outlook eliminado correctamente.',
        ]);
    }

    public function secret(OutlookAccount $correos_outlook): JsonResponse
    {
        $this->authorize('viewSensitive', $correos_outlook);

        return response()->json([
            'success' => true,
            'data' => ['password' => (string) $correos_outlook->getAttribute('contraseña')],
        ])->header('Cache-Control', 'no-store, private');
    }

    private function normalizeData(array $validated): array
    {
        $data = $validated;
        $password = $this->passwordFrom($validated);

        if (filled($password)) {
            $data['contraseña'] = $password;
        }

        unset($data['password'], $data['contraseña_legacy']);

        $data['servidor_entrada'] = filled($data['servidor_entrada'] ?? null) ? $data['servidor_entrada'] : 'mail.totalground.com';
        $data['puerto_entrada'] = filled($data['puerto_entrada'] ?? null) ? (string) $data['puerto_entrada'] : '995';
        $data['ssl_entrada'] = isset($data['ssl_entrada']) ? (bool) $data['ssl_entrada'] : true;
        $data['servidor_salida'] = filled($data['servidor_salida'] ?? null) ? $data['servidor_salida'] : 'mail.totalground.com';
        $data['puerto_salida'] = filled($data['puerto_salida'] ?? null) ? (string) $data['puerto_salida'] : '465';
        $data['cifrado_salida'] = filled($data['cifrado_salida'] ?? null) ? $data['cifrado_salida'] : 'SSL/TLS';

        return $data;
    }

    private function passwordFrom(array $data): ?string
    {
        if (array_key_exists('password', $data)) {
            return $data['password'];
        }

        if (array_key_exists('contraseña', $data)) {
            return $data['contraseña'];
        }

        // Compatibility with clients that still send the old mis-encoded field.
        foreach ($data as $key => $value) {
            if (is_string($key) && str_contains($key, 'contrase') && str_ends_with($key, 'a')) {
                return $value;
            }
        }

        return null;
    }
}
