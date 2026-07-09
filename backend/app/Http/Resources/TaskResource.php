<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project' => new ProjectResource($this->whenLoaded('project')),
            'title' => $this->title,
            'description' => $this->description,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'progress' => $this->progress,
            'target_date' => $this->target_date?->toDateString(),
            'target_time' => $this->target_time,
            'assignees' => UserResource::collection($this->whenLoaded('assignees')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'updates' => TaskUpdateResource::collection($this->whenLoaded('updates')),
            'updates_count' => $this->whenCounted('updates'),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
