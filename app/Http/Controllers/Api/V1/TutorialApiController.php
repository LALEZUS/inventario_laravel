<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\TutorialRequest;
use App\Http\Resources\TutorialResource;
use App\Models\Tutorial;
use App\Services\AuditLogger;
use App\Services\LibraryFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TutorialApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tutorial::class);

        $query = Tutorial::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $items = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->latest()
        )->paginate(18);

        return response()->json([
            'success' => true,
            'data' => TutorialResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(Tutorial $tutorial): JsonResponse
    {
        $this->authorize('view', $tutorial);

        return response()->json([
            'success' => true,
            'data' => new TutorialResource($tutorial, true),
        ]);
    }

    public function store(TutorialRequest $request, AuditLogger $logger): JsonResponse
    {
        $this->authorize('create', Tutorial::class);

        $file = $request->file('pdf_file');
        $path = $file->storeAs(
            'library/tutorials',
            now()->format('YmdHis') . '-' . Str::uuid() . '.pdf',
            'local'
        );

        $data = [
            'title' => $request->input('title'),
            'category' => $request->input('category'),
            'description' => $request->input('description'),
            'comments' => $request->input('comments'),
            'content_url' => 'laravel-local:' . $path,
        ];

        $tutorial = DB::transaction(function () use ($data, $logger) {
            $m = Tutorial::create($data);
            $logger->record('create', 'tutorials', $m->id, null, $m->getAttributes());
            return $m;
        });

        return response()->json([
            'success' => true,
            'message' => 'Tutorial registrado correctamente.',
            'data' => new TutorialResource($tutorial, true),
        ], 201);
    }

    public function update(TutorialRequest $request, Tutorial $tutorial, AuditLogger $logger, LibraryFile $files): JsonResponse
    {
        $this->authorize('update', $tutorial);

        $before = $tutorial->getAttributes();
        $oldContentUrl = $tutorial->content_url;

        $updateData = [
            'title' => $request->input('title'),
            'category' => $request->input('category'),
            'description' => $request->input('description'),
            'comments' => $request->input('comments'),
        ];

        $newPath = null;
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $newPath = $file->storeAs(
                'library/tutorials',
                now()->format('YmdHis') . '-' . Str::uuid() . '.pdf',
                'local'
            );
            $updateData['content_url'] = 'laravel-local:' . $newPath;
        }

        DB::transaction(function () use ($tutorial, $updateData, $before, $logger) {
            $tutorial->update($updateData);
            $logger->record('update', 'tutorials', $tutorial->id, $before, $tutorial->fresh()->getAttributes());
        });

        // Si se subió un nuevo PDF y la BD se actualizó correctamente, borrar el PDF anterior
        if ($newPath !== null && !empty($oldContentUrl)) {
            try {
                $files->delete($oldContentUrl, 'uploads/tutorials');
            } catch (\Throwable $e) {
                // Ignore silent deletion errors on old legacy files
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Tutorial actualizado correctamente.',
            'data' => new TutorialResource($tutorial->fresh(), true),
        ]);
    }

    public function destroy(Tutorial $tutorial, AuditLogger $logger, LibraryFile $files): JsonResponse
    {
        $this->authorize('delete', $tutorial);

        $before = $tutorial->getAttributes();
        $contentUrl = $tutorial->content_url;

        if ($contentUrl) {
            try {
                $files->delete($contentUrl, 'uploads/tutorials');
            } catch (\Throwable $e) {
                // Proceed to delete DB record even if file is missing
            }
        }

        $tutorial->delete();

        $logger->record('delete', 'tutorials', $tutorial->id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Tutorial eliminado correctamente.',
        ]);
    }

    public function preview(Tutorial $tutorial, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $tutorial);

        if (empty($tutorial->content_url)) {
            abort(404, 'El tutorial no cuenta con un archivo PDF adjunto.');
        }

        $path = $files->path($tutorial->content_url, 'uploads/tutorials');
        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function download(Tutorial $tutorial, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $tutorial);

        if (empty($tutorial->content_url)) {
            abort(404, 'El tutorial no cuenta con un archivo PDF adjunto.');
        }

        $path = $files->path($tutorial->content_url, 'uploads/tutorials');
        $downloadName = Str::slug($tutorial->title) . '.pdf';

        return response()->download($path, $downloadName, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }
}
