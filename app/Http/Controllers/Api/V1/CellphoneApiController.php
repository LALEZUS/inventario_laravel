<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CellphoneRequest;
use App\Http\Resources\CellphoneResource;
use App\Models\AssetFile;
use App\Models\AuditLog;
use App\Models\Cellphone;
use App\Services\AuditLogger;
use App\Services\CellphoneService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CellphoneApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cellphone::class);

        $search = trim((string) $request->query('search'));
        $query = Cellphone::with('employee')
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.$search.'%';
                $query->where(function ($q) use ($term) {
                    $q->where('employee_name_legacy', 'like', $term)
                        ->orWhere('model', 'like', $term)
                        ->orWhere('area', 'like', $term)
                        ->orWhere('email_account', 'like', $term)
                        ->orWhere('phone_number', 'like', $term)
                        ->orWhere('status', 'like', $term)
                        ->orWhereHas('employee', fn ($e) => $e->where('full_name', 'like', $term));
                });
            });

        $cellphones = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->orderBy('employee_name_legacy')
        )->paginate($request->integer('per_page', 20))
            ->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Celulares obtenidos correctamente.',
            'data' => CellphoneResource::collection($cellphones),
            'meta' => [
                'pagination' => [
                    'total' => $cellphones->total(),
                    'per_page' => $cellphones->perPage(),
                    'current_page' => $cellphones->currentPage(),
                    'last_page' => $cellphones->lastPage(),
                ],
            ],
        ], 200);
    }

    public function store(CellphoneRequest $request, CellphoneService $service): JsonResponse
    {
        $this->authorize('create', Cellphone::class);

        $cellphone = $service->store($request->validated(), $request->boolean('has_app_lock'), $request->user());
        $cellphone->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Celular registrado correctamente.',
            'data' => new CellphoneResource($cellphone),
        ], 201);
    }

    public function show(Cellphone $cellphone): JsonResponse
    {
        $this->authorize('view', $cellphone);

        $cellphone->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Celular obtenido correctamente.',
            'data' => new CellphoneResource($cellphone),
        ], 200);
    }

    public function update(
        CellphoneRequest $request,
        Cellphone $cellphone,
        CellphoneService $service
    ): JsonResponse {
        $this->authorize('update', $cellphone);

        $updated = $service->update($cellphone->id, $request->validated(), $request->boolean('has_app_lock'), $request->user());
        $updated->load('employee');

        return response()->json([
            'success' => true,
            'message' => 'Celular actualizado correctamente.',
            'data' => new CellphoneResource($updated),
        ], 200);
    }

    public function destroy(Cellphone $cellphone, AuditLogger $audit): JsonResponse
    {
        $this->authorize('delete', $cellphone);

        $before = $cellphone->getAttributes();
        DB::transaction(function () use ($cellphone, $before, $audit) {
            $cellphone->delete();
            $audit->record('delete', 'cellphones', $cellphone->id, $before, null, request()->user());
        });

        return response()->json([
            'success' => true,
            'message' => 'Celular eliminado correctamente.',
        ], 200);
    }

    public function credentials(Request $request, Cellphone $cellphone): JsonResponse
    {
        $this->authorize('viewSensitive', $cellphone);

        return response()->json([
            'success' => true,
            'message' => 'Credenciales obtenidas correctamente.',
            'data' => [
                'password' => $cellphone->password,
                'updated_password' => $cellphone->updated_password,
                'app_lock_password' => $cellphone->app_lock_password,
                'app_lock_answer' => $cellphone->app_lock_answer,
                'app_lock_pattern' => $cellphone->app_lock_pattern,
            ],
        ])->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function photos(Request $request, Cellphone $cellphone): JsonResponse
    {
        $this->authorize('view', $cellphone);

        $photos = $cellphone->files()
            ->where('file_type', 'like', 'image/%')
            ->latest('uploaded_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Fotos obtenidas correctamente.',
            'data' => $photos->map(fn ($p) => [
                'id' => $p->id,
                'label' => $p->label,
                'original_name' => $p->original_name,
                'file_type' => $p->file_type,
                'file_size' => $p->file_size,
                'uploaded_at' => $p->uploaded_at?->toIso8601String(),
                'preview_url' => route('api.v1.files.preview', $p),
                'download_url' => route('api.v1.files.download', $p),
            ]),
        ]);
    }

    public function uploadPhoto(Request $request, Cellphone $cellphone, AuditLogger $audit): JsonResponse
    {
        $this->authorize('upload', $cellphone);

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['photo'];
        $path = $file->store('assets/cellphones/'.$cellphone->id, 'local');

        $assetFile = AssetFile::create([
            'asset_type' => 'cellphone',
            'asset_id' => $cellphone->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => 'laravel-local:'.$path,
            'file_type' => $file->getClientMimeType() ?: 'image/jpeg',
            'file_size' => $file->getSize(),
            'label' => $validated['label'] ?? 'Foto de evidencia',
            'comments' => 'Subida desde Flutter API',
        ]);

        $audit->record('upload', 'cellphones', $cellphone->id, null, [
            'asset_file_id' => $assetFile->id,
            'filename' => $file->getClientOriginalName(),
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Foto subida correctamente.',
            'data' => [
                'id' => $assetFile->id,
                'label' => $assetFile->label,
                'original_name' => $assetFile->original_name,
                'file_type' => $assetFile->file_type,
                'file_size' => $assetFile->file_size,
                'uploaded_at' => $assetFile->uploaded_at?->toIso8601String(),
                'preview_url' => route('api.v1.files.preview', $assetFile),
                'download_url' => route('api.v1.files.download', $assetFile),
            ],
        ], 201);
    }

    public function files(Request $request, Cellphone $cellphone): JsonResponse
    {
        $this->authorize('view', $cellphone);

        $files = $cellphone->files()
            ->latest('uploaded_at')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Archivos adjuntos obtenidos correctamente.',
            'data' => $files->map(fn ($f) => [
                'id' => $f->id,
                'label' => $f->label,
                'original_name' => $f->original_name,
                'file_type' => $f->file_type,
                'file_size' => $f->file_size,
                'uploaded_at' => $f->uploaded_at?->toIso8601String(),
                'download_url' => route('api.v1.files.download', $f),
                'preview_url' => (str_starts_with((string) $f->file_type, 'image/') || in_array(strtolower((string) $f->file_type), ['application/pdf', 'text/plain', 'text/csv'], true))
                    ? route('api.v1.files.preview', $f)
                    : null,
            ]),
        ]);
    }

    public function uploadFile(Request $request, Cellphone $cellphone, AuditLogger $audit): JsonResponse
    {
        $this->authorize('upload', $cellphone);

        $validated = $request->validate([
            'file' => ['required', 'file', 'max:20480'],
            'label' => ['nullable', 'string', 'max:120'],
        ]);

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $validated['file'];
        $path = $file->store('assets/cellphones/'.$cellphone->id, 'local');

        $assetFile = AssetFile::create([
            'asset_type' => 'cellphone',
            'asset_id' => $cellphone->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => 'laravel-local:'.$path,
            'file_type' => $file->getClientMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'label' => $validated['label'] ?? 'Documento adjunto',
            'comments' => 'Subido desde Flutter API',
        ]);

        $audit->record('upload', 'cellphones', $cellphone->id, null, [
            'asset_file_id' => $assetFile->id,
            'filename' => $file->getClientOriginalName(),
        ], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Archivo subido correctamente.',
            'data' => [
                'id' => $assetFile->id,
                'label' => $assetFile->label,
                'original_name' => $assetFile->original_name,
                'file_type' => $assetFile->file_type,
                'file_size' => $assetFile->file_size,
                'uploaded_at' => $assetFile->uploaded_at?->toIso8601String(),
                'download_url' => route('api.v1.files.download', $assetFile),
                'preview_url' => (str_starts_with((string) $assetFile->file_type, 'image/') || in_array(strtolower((string) $assetFile->file_type), ['application/pdf', 'text/plain', 'text/csv'], true))
                    ? route('api.v1.files.preview', $assetFile)
                    : null,
            ],
        ], 201);
    }

    public function auditLogs(Request $request, Cellphone $cellphone): JsonResponse
    {
        $this->authorize('viewAudit', $cellphone);

        $fileIds = $cellphone->files()->pluck('id')->all();

        $logs = AuditLog::query()
            ->where(function ($q) use ($cellphone, $fileIds) {
                $q->where(fn ($sub) => $sub->where('entity', 'cellphones')->where('entity_id', (string) $cellphone->id))
                    ->orWhere(fn ($sub) => $sub->where('entity', 'asset_files')->whereIn('entity_id', array_map('strval', $fileIds)));
            })
            ->latest('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Bitácora de auditoría obtenida correctamente.',
            'data' => $logs->map(function ($log) {
                $actionLabel = match (strtolower((string) $log->action)) {
                    'create', 'crear' => 'Creación de Registro',
                    'update', 'actualizar', 'editar' => 'Actualización de Datos',
                    'delete', 'eliminar' => 'Eliminación',
                    'upload' => 'Archivo/Foto Adjuntada',
                    default => ucfirst($log->action),
                };

                $entityLabel = match (strtolower((string) $log->entity)) {
                    'cellphones' => 'Celular',
                    'asset_files' => 'Archivo/Foto',
                    default => $log->entity,
                };

                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'action_label' => $actionLabel,
                    'entity' => $log->entity,
                    'entity_label' => $entityLabel,
                    'description' => "Acción '{$actionLabel}' realizada sobre {$entityLabel}",
                    'user_name' => $log->user_name ?: 'Sistema',
                    'role' => $log->role ?: 'sistema',
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            }),
        ]);
    }
}
