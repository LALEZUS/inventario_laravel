<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileCatalogRequest;
use App\Http\Resources\FileCatalogResource;
use App\Models\AuditLog;
use App\Models\FileCatalog;
use App\Services\AuditLogger;
use App\Services\LibraryFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class FileCatalogApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FileCatalog::class);

        $search = trim((string) $request->query('search'));
        $perPage = min(max((int) $request->query('per_page', 25), 1), 100);

        $query = FileCatalog::with('uploader')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sq) use ($search) {
                    $sq->where('original_name', 'like', "%{$search}%")
                        ->orWhere('alias_name', 'like', "%{$search}%")
                        ->orWhere('comments', 'like', "%{$search}%");
                });
            });

        $paginator = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->latest('upload_date')
        )->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => FileCatalogResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, FileCatalog $fileCatalog): JsonResponse
    {
        $this->authorize('view', $fileCatalog);

        $fileCatalog->load('uploader');
        $data = (new FileCatalogResource($fileCatalog))->resolve($request);

        if ($request->user()->can('viewAudit', $fileCatalog)) {
            $data['audit_logs'] = AuditLog::where('entity', 'ftp_catalog')
                ->where('entity_id', (string) $fileCatalog->id)
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($log) => [
                    'id' => $log->id,
                    'user' => $log->user_name ?: ($log->user?->name ?? 'Sistema'),
                    'action' => $log->action,
                    'created_at' => $log->created_at?->toIso8601String(),
                ]);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function store(FileCatalogRequest $request, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorize('create', FileCatalog::class);

        $validated = $request->validated();
        $file = $request->file('file');

        $filename = now()->format('YmdHis') . '-' . Str::uuid() . '-' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . strtolower($file->getClientOriginalExtension());
        $path = $file->storeAs('library/files', $filename, 'local');

        $catalog = FileCatalog::create([
            'original_name' => $file->getClientOriginalName(),
            'alias_name' => $validated['alias_name'] ?? null,
            'file_path' => 'laravel-local:' . $path,
            'file_size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
            'comments' => $validated['comments'] ?? null,
        ]);

        $auditLogger->record('create', 'ftp_catalog', $catalog->id, null, $catalog->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Archivo subido correctamente.',
            'data' => new FileCatalogResource($catalog->load('uploader')),
        ], 201);
    }

    public function update(FileCatalogRequest $request, FileCatalog $fileCatalog, AuditLogger $auditLogger): JsonResponse
    {
        $this->authorize('update', $fileCatalog);

        $before = $fileCatalog->getAttributes();
        $validated = $request->validated();
        unset($validated['file']);

        $fileCatalog->update($validated);

        $auditLogger->record('update', 'ftp_catalog', $fileCatalog->id, $before, $fileCatalog->fresh()->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Información del archivo actualizada.',
            'data' => new FileCatalogResource($fileCatalog->load('uploader')),
        ]);
    }

    public function destroy(FileCatalog $fileCatalog, AuditLogger $auditLogger, LibraryFile $files): JsonResponse
    {
        $this->authorize('delete', $fileCatalog);

        $before = $fileCatalog->getAttributes();
        $files->delete($fileCatalog->file_path, 'ftp_files');
        $fileCatalog->delete();

        $auditLogger->record('delete', 'ftp_catalog', $fileCatalog->id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Archivo eliminado correctamente.',
        ]);
    }

    public function download(FileCatalog $fileCatalog, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $fileCatalog);

        $path = $files->path($fileCatalog->file_path, 'ftp_files');

        return response()->download($path, $fileCatalog->original_name);
    }

    public function preview(FileCatalog $fileCatalog, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $fileCatalog);

        $path = $files->path($fileCatalog->file_path, 'ftp_files');
        $mime = mime_content_type($path) ?: 'application/octet-stream';

        return response()->file($path, [
            'Content-Type' => $mime,
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}
