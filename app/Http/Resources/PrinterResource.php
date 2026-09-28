<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrinterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial' => $this->serial,
            'code' => $this->code,
            'ip_address' => $this->ip_address,
            'zone' => $this->zone,
            'assigned_to' => $this->assigned_name,
            'is_network' => (bool) $this->is_network,
            'supply_type' => $this->supply_type,
            'ink_type' => $this->ink_type,
            'linked_inks' => $this->linkedInkIds(),
            'linked_toner' => $this->linkedTonerIds(),
            'status' => $this->status,
            'comments' => $this->comments,
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'department' => $this->employee->department,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}