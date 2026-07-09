<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'job_title' => $this->job_title,
            'avatar' => $this->avatar ? Storage::url($this->avatar) : null,
            'is_admin' => (bool) $this->is_admin,
            'role' => $this->whenPivotLoaded('project_members', fn () => $this->pivot->role),
            'created_at' => $this->created_at,
        ];
    }
}
