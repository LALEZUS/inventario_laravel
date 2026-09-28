<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeripheralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial' => $this->serial,
            'category' => $this->category,
            'status' => $this->status,
            'location' => $this->location,
            'quantity' => (int) $this->quantity,
            'comments' => $this->comments,
            'assigned_to' => $this->assigned_name,
            'computer_id' => $this->computer_id,
            'employee_id' => $this->employee_id,
            'employee' => $this->employee ? [
                'id' => $this->employee->id,
                'full_name' => $this->employee->full_name,
                'department' => $this->employee->department,
            ] : null,
            'computer' => $this->computer ? [
                'id' => $this->computer->id,
                'code' => $this->computer->code,
                'name' => $this->computer->name,
            ] : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
