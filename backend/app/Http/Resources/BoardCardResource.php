<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'stage' => $this->stage,
            'position' => $this->position,
            'creator' => new UserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at,
        ];
    }
}
