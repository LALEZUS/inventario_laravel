<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CellphoneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'employee_name' => $this->assigned_name,
            'model' => $this->model,
            'area' => $this->area,
            'email_account' => $this->email_account,
            'recovery_account' => $this->recovery_account,
            'phone_number' => $this->phone_number,
            'birth_date' => $this->birth_date,
            'status' => $this->status,
            'has_app_lock' => (bool) $this->has_app_lock,
            'update_note' => $this->update_note,
            'comments' => $this->comments,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
