<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use App\Services\LibraryFile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TutorialResource extends JsonResource
{
    public bool $isDetail = false;

    public function __construct($resource, bool $isDetail = false)
    {
        parent::__construct($resource);
        $this->isDetail = $isDetail;
    }

    public function toArray(Request $request): array
    {
        $fileSize = $this->getFileSizeBytes();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'category' => $this->category,
            'description' => $this->description,
            'comments' => $this->comments,
            'extension' => 'pdf',
            'file_size' => $fileSize,
            'formatted_file_size' => $this->formatSize($fileSize),
            'has_file' => !empty($this->content_url),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'preview_url' => route('api.v1.tutorials.preview', $this->id),
            'download_url' => route('api.v1.tutorials.download', $this->id),
            'audit_logs' => $this->when(
                $this->isDetail && $request->user()?->can('viewAudit', $this->resource),
                fn () => AuditLog::where('entity', 'tutorials')
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
        if (empty($this->content_url)) {
            return 0;
        }

        try {
            $libraryFile = new LibraryFile();
            $path = $libraryFile->path($this->content_url, 'uploads/tutorials');
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