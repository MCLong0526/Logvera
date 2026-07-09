<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BoardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'members' => UserResource::collection($this->whenLoaded('members')),
            'cards' => BoardCardResource::collection($this->whenLoaded('cards')),
            'members_count' => $this->whenCounted('members'),
            'cards_count' => $this->whenCounted('cards'),
            'is_owner' => $this->owner_id === $request->user()?->id,
            'created_at' => $this->created_at,
        ];
    }
}
