<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssetFile extends Model
{
    public const CREATED_AT = 'uploaded_at';
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function extension(): string
    {
        return strtolower((string) pathinfo($this->original_name, PATHINFO_EXTENSION));
    }

    public function isImage(): bool
    {
        return str_starts_with(strtolower((string) $this->file_type), 'image/')
            || in_array($this->extension(), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
    }

    public function isPreviewable(): bool
    {
        return $this->isImage()
            || in_array(strtolower((string) $this->file_type), ['application/pdf', 'text/plain', 'text/csv'], true)
            || in_array($this->extension(), ['pdf', 'txt', 'csv'], true);
    }

    public function previewMimeType(): string
    {
        $type = strtolower((string) $this->file_type);
        if ($this->isImage() && str_starts_with($type, 'image/')) return $type;
        if (in_array($type, ['application/pdf', 'text/plain', 'text/csv'], true)) return $type;

        return match ($this->extension()) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            default => 'text/plain',
        };
    }

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }
}
