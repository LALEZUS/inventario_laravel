<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HardwareAssetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $activeAssignment = $this->relationLoaded('assignments')
            ? $this->assignments->firstWhere('date_returned', null)
            : $this->assignments()->whereNull('date_returned')->latest('date_assigned')->first();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'category' => $this->category,
            'format' => $this->format,
            'brand' => $this->brand,
            'model' => $this->model,
            'serial' => $this->serial,
            'processor' => $this->processor,
            'ram' => $this->ram,
            'storage' => $this->storage,
            'os' => $this->os,
            'os_version' => $this->os_version,
            'architecture' => $this->architecture,
            'bios' => $this->bios,
            'motherboard' => $this->motherboard,
            'gpu' => $this->gpu,
            'network_adapter' => $this->network_adapter,
            'mac_address' => $this->mac_address,
            'secure_boot' => $this->secure_boot,
            'tpm' => $this->tpm,
            'status' => $this->status,
            'location' => $this->location,
            'zone' => $this->zone,
            'value' => $this->value ? (float) $this->value : null,
            'has_office' => (bool) $this->has_office,
            'has_winrar' => (bool) $this->has_winrar,
            'has_reader' => (bool) $this->has_reader,
            'has_server' => (bool) $this->has_server,
            'has_printer' => (bool) $this->has_printer,
            'delivery_date' => $this->delivery_date?->format('Y-m-d'),
            'comments' => $this->comments,
            'assigned_user' => $this->assigned_to,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'current_assignment' => $activeAssignment ? new AssignmentResource($activeAssignment) : null,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}