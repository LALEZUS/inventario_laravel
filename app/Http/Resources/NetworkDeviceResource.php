<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NetworkDeviceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'device_name' => $this->device_name,
            'device_type' => $this->device_type,
            'ip_address' => $this->ip_address,
            'mac_address' => $this->mac_address,
            'location' => $this->location,
            'brand' => $this->brand,
            'status' => $this->status,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}