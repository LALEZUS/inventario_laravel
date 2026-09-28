<?php

namespace App\Http\Resources;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountCredentialResource extends JsonResource
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
            'email' => $this->email,
            'account_type' => $this->account_type,
            'assigned_to' => $this->assigned_to,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->employee?->full_name ?: $this->assigned_to,
            'status' => $this->status,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'audit_logs' => $this->when(
                $this->isDetail && $request->user()?->can('viewAudit', $this->resource),
                fn () => AuditLog::where('entity', 'account_management')
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