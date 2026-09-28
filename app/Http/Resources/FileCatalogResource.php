<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileCatalogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $ext = strtolower(pathinfo($this->original_name ?? '', PATHINFO_EXTENSION));

        return [
            'id' => $this->id,
            'original_name' => $this->original_name,
            'alias_name' => $this->alias_name,
            'display_name' => $this->alias_name ?: $this->original_name,
            'extension' => $ext,
            'file_size' => $this->file_size ? (int) $this->file_size : 0,
            'formatted_file_size' => number_format(($this->file_size ?? 0) / 1048576, 2) . ' MB',
            'uploaded_by' => $this->uploader?->display_name ?: 'Importado',
            'uploaded_by_id' => $this->uploaded_by,
            'upload_date' => $this->upload_date ? $this->upload_date->toIso8601String() : null,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
