<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_type' => $this->asset_type,
            'asset_id' => $this->asset_id,
            'employee_id' => $this->employee_id,
            'assigned_to' => $this->assigned_to,
            'department' => $this->department,
            'date_assigned' => $this->date_assigned?->format('Y-m-d'),
            'date_returned' => $this->date_returned?->format('Y-m-d'),
            'condition_on_assign' => $this->condition_on_assign,
            'condition_on_return' => $this->condition_on_return,
            'notes' => $this->notes,
            'comments' => $this->comments,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
