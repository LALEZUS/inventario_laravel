<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\BelongsTo;
class FileCatalog extends Model
{
    protected $table = 'ftp_catalog';
    protected $guarded = [];

    protected function casts(): array
    {
        return ['upload_date' => 'datetime'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extension(): string
    {
        return strtolower(pathinfo((string) $this->original_name, PATHINFO_EXTENSION));
    }

    public function typeGroup(): string
    {
        return match (true) {
            in_array($this->extension(), ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'], true) => 'archive',
            in_array($this->extension(), ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'bmp', 'ico'], true) => 'image',
            $this->extension() === 'pdf' => 'pdf',
            in_array($this->extension(), ['doc', 'docx', 'odt', 'rtf'], true) => 'word',
            in_array($this->extension(), ['xls', 'xlsx', 'ods', 'csv'], true) => 'spreadsheet',
            in_array($this->extension(), ['ppt', 'pptx', 'odp'], true) => 'slides',
            in_array($this->extension(), ['html', 'htm', 'css', 'js', 'ts', 'php', 'json', 'xml', 'yml', 'yaml', 'nfo'], true) => 'code',
            in_array($this->extension(), ['mp3', 'wav', 'ogg', 'flac', 'm4a'], true) => 'audio',
            in_array($this->extension(), ['mp4', 'avi', 'mov', 'mkv', 'webm'], true) => 'video',
            in_array($this->extension(), ['exe', 'msi', 'bat', 'cmd'], true) => 'installer',
            in_array($this->extension(), ['txt', 'md', 'log'], true) => 'text',
            default => 'generic',
        };
    }

    public function formattedSize(): string
    {
        $bytes = (int) ($this->file_size ?? 0);
        if ($bytes <= 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    public function canPreview(): bool
    {
        return in_array($this->typeGroup(), ['image', 'pdf', 'code', 'text', 'audio', 'video'], true);
    }

    public function iconClass(): string
    {
        return match ($this->typeGroup()) {
            'archive' => 'bi-file-earmark-zip',
            'image' => 'bi-file-earmark-image',
            'pdf' => 'bi-file-earmark-pdf',
            'word' => 'bi-file-earmark-word',
            'spreadsheet' => 'bi-file-earmark-spreadsheet',
            'slides' => 'bi-file-earmark-slides',
            'code' => 'bi-file-earmark-code',
            'audio' => 'bi-file-earmark-music',
            'video' => 'bi-file-earmark-play',
            'installer' => 'bi-file-earmark-binary',
            'text' => 'bi-file-earmark-text',
            default => 'bi-file-earmark',
        };
    }
}
