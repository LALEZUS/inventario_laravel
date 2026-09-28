<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EnterpriseNetworkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'network_name' => $this->network_name,
            'vlan' => $this->vlan,
            'location' => $this->location,
            'encryption' => $this->encryption,
            'comments' => $this->comments,
            'notes_extra' => $this->notes_extra,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}