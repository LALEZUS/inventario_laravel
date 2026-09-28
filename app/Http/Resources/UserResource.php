<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'full_name' => $this->full_name,
            'display_name' => $this->display_name,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'comments' => $this->comments,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
