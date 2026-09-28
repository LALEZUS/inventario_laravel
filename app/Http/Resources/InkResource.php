<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'brand' => $this->brand,
            'model' => $this->model,
            'color' => $this->color,
            'color_key' => $this->colorKey(),
            'type' => $this->type,
            'capacity' => $this->capacity,
            'quantity' => (int) $this->quantity,
            'purchase_date' => $this->dateValue('purchase_date'),
            'expiry_date' => $this->dateValue('expiry_date'),
            'status' => $this->status,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}