<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OutlookAccountResource extends JsonResource
{
    public bool $isDetail = false;

    public function __construct($resource, bool $isDetail = false)
    {
        parent::__construct($resource);
        $this->isDetail = $isDetail;
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'correo' => $this->correo,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee?->full_name,
            'employee_department' => $this->employee?->department,
            'estatus' => $this->estatus,
            'servidor_entrada' => $this->servidor_entrada,
            'puerto_entrada' => (string) ($this->puerto_entrada ?? '995'),
            'ssl_entrada' => (bool) $this->ssl_entrada,
            'servidor_salida' => $this->servidor_salida,
            'puerto_salida' => (string) ($this->puerto_salida ?? '465'),
            'cifrado_salida' => $this->cifrado_salida,
            'comentarios' => $this->comentarios,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'audit_logs' => $this->when(
                $this->isDetail && $request->user()?->can('viewAudit', $this->resource),
                fn () => AuditLog::where('entity', 'correos_outlook')
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
}