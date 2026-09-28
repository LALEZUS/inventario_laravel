<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\GalleryItemRequest;
use App\Http\Resources\GalleryItemResource;
use App\Models\GalleryItem;
use App\Services\AuditLogger;
use App\Services\LibraryFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class GalleryApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', GalleryItem::class);

        $query = GalleryItem::query();

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $items = \App\Support\RecentRecords::apply(
            $query,
            $request,
            fn ($query) => $query->latest('upload_date')
        )->paginate(18);

        return response()->json([
            'success' => true,
            'data' => GalleryItemResource::collection($items),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(GalleryItem $galleryItem): JsonResponse
    {
        $this->authorize('view', $galleryItem);

        return response()->json([
            'success' => true,
            'data' => new GalleryItemResource($galleryItem, true),
        ]);
    }

    public function store(GalleryItemRequest $request, AuditLogger $logger): JsonResponse
    {
        $this->authorize('create', GalleryItem::class);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $path = $file->storeAs(
            'library/gallery',
            now()->format('YmdHis') . '-' . Str::uuid() . '.' . $extension,
            'local'
        );

        $item = GalleryItem::create([
            'title' => $request->input('title'),
            'notes' => $request->input('notes'),
            'filename' => 'laravel-local:' . $path,
            'upload_date' => now(),
        ]);

        $logger->record('create', 'gallery', $item->id, null, $item->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Imagen agregada a la galería correctamente.',
            'data' => new GalleryItemResource($item, true),
        ], 201);
    }

    public function update(GalleryItemRequest $request, GalleryItem $galleryItem, AuditLogger $logger, LibraryFile $files): JsonResponse
    {
        $this->authorize('update', $galleryItem);

        $before = $galleryItem->getAttributes();
        $oldFilename = $galleryItem->filename;

        $updateData = [
            'title' => $request->input('title'),
            'notes' => $request->input('notes'),
        ];

        $newPath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
            $newPath = $file->storeAs(
                'library/gallery',
                now()->format('YmdHis') . '-' . Str::uuid() . '.' . $extension,
                'local'
            );
            $updateData['filename'] = 'laravel-local:' . $newPath;
        }

        $galleryItem->update($updateData);

        // Si se subió una nueva imagen y la BD se actualizó correctamente, borrar el archivo anterior
        if ($newPath !== null && !empty($oldFilename)) {
            try {
                $files->delete($oldFilename, 'uploads/gallery');
                $this->clearThumbnail($galleryItem->id);
            } catch (\Throwable $e) {
                // Ignore silent deletion errors on old legacy files
            }
        }

        $logger->record('update', 'gallery', $galleryItem->id, $before, $galleryItem->fresh()->getAttributes());

        return response()->json([
            'success' => true,
            'message' => 'Imagen de galería actualizada correctamente.',
            'data' => new GalleryItemResource($galleryItem->fresh(), true),
        ]);
    }

    public function destroy(GalleryItem $galleryItem, AuditLogger $logger, LibraryFile $files): JsonResponse
    {
        $this->authorize('delete', $galleryItem);

        $before = $galleryItem->getAttributes();
        $filename = $galleryItem->filename;

        try {
            $files->delete($filename, 'uploads/gallery');
            $this->clearThumbnail($galleryItem->id);
        } catch (\Throwable $e) {
            // Proceed to delete database record even if physical file missing
        }

        $galleryItem->delete();

        $logger->record('delete', 'gallery', $galleryItem->id, $before, null);

        return response()->json([
            'success' => true,
            'message' => 'Imagen eliminada de la galería correctamente.',
        ]);
    }

    public function image(GalleryItem $galleryItem, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $galleryItem);

        $path = $files->path($galleryItem->filename, 'uploads/gallery');
        return response()->file($path, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    public function thumbnail(GalleryItem $galleryItem, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $galleryItem);

        $originalPath = $files->path($galleryItem->filename, 'uploads/gallery');
        $thumbDir = Storage::disk('local')->path('library/gallery/thumbnails');
        if (!is_dir($thumbDir)) {
            mkdir($thumbDir, 0755, true);
        }

        $thumbPath = $thumbDir . DIRECTORY_SEPARATOR . 'thumb_' . $galleryItem->id . '.jpg';

        if (!file_exists($thumbPath) || filemtime($thumbPath) < filemtime($originalPath)) {
            $this->generateThumbnail($originalPath, $thumbPath, 400, 400);
        }

        if (file_exists($thumbPath)) {
            return response()->file($thumbPath, [
                'Content-Type' => 'image/jpeg',
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        // Fallback to original image if GD thumbnail generation fails
        return response()->file($originalPath);
    }

    public function download(GalleryItem $galleryItem, LibraryFile $files): BinaryFileResponse
    {
        $this->authorize('view', $galleryItem);

        $path = $files->path($galleryItem->filename, 'uploads/gallery');
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg';
        $downloadName = Str::slug($galleryItem->title) . '.' . $extension;

        return response()->download($path, $downloadName, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    private function generateThumbnail(string $src, string $dest, int $maxWidth, int $maxHeight): bool
    {
        if (!extension_loaded('gd')) {
            return false;
        }

        $info = @getimagesize($src);
        if (!$info) {
            return false;
        }

        $width = $info[0];
        $height = $info[1];
        $type = $info[2];

        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = @imagecreatefromjpeg($src);
                break;
            case IMAGETYPE_PNG:
                $image = @imagecreatefrompng($src);
                break;
            case IMAGETYPE_GIF:
                $image = @imagecreatefromgif($src);
                break;
            case IMAGETYPE_WEBP:
                $image = @imagecreatefromwebp($src);
                break;
            default:
                return false;
        }

        if (!$image) {
            return false;
        }

        $ratio = min($maxWidth / $width, $maxHeight / $height);
        if ($ratio >= 1.0) {
            $newWidth = $width;
            $newHeight = $height;
        } else {
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);
        }

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagejpeg($thumb, $dest, 85);

        imagedestroy($image);
        imagedestroy($thumb);

        return true;
    }

    private function clearThumbnail(int $id): void
    {
        $thumbPath = Storage::disk('local')->path('library/gallery/thumbnails/thumb_' . $id . '.jpg');
        if (file_exists($thumbPath)) {
            @unlink($thumbPath);
        }
    }
}
