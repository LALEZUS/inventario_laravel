<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use App\Services\LibraryFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class GalleryItemResource extends JsonResource
{
    public bool $isDetail = false;

    public function __construct($resource, bool $isDetail = false)
    {
        parent::__construct($resource);
        $this->isDetail = $isDetail;
    }

    public function toArray(Request $request): array
    {
        $extension = pathinfo($this->filename, PATHINFO_EXTENSION);
        if (empty($extension)) {
            $extension = 'jpg';
        }

        $fileSize = $this->getFileSizeBytes();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'notes' => $this->notes,
            'extension' => strtolower($extension),
            'file_size' => $fileSize,
            'formatted_file_size' => $this->formatSize($fileSize),
            'upload_date' => $this->upload_date?->toIso8601String(),
            'created_at' => $this->upload_date?->toIso8601String(),
            'updated_at' => null,
            'thumbnail_url' => route('api.v1.gallery.thumbnail', $this->id),
            'image_url' => route('api.v1.gallery.image', $this->id),
            'download_url' => route('api.v1.gallery.download', $this->id),
            'audit_logs' => $this->when(
                $this->isDetail && $request->user()?->can('viewAudit', $this->resource),
                fn () => AuditLog::where('entity', 'gallery')
                    ->where('entity_id', (string) $this->id)
                    ->latest()
                    ->limit(20)
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'user' => $log->user_name ?? 'Sistema',
                        'action' => $log->action,
                        'created_at' => $log->created_at?->toIso8601String(),
                    ])
            ),
        ];
    }

    private function getFileSizeBytes(): int
    {
        try {
            $libraryFile = new LibraryFile();
            $path = $libraryFile->path($this->filename, 'uploads/gallery');
            return is_file($path) ? filesize($path) : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0.00 MB';
        }
        return number_format($bytes / (1024 * 1024), 2) . ' MB';
    }
}