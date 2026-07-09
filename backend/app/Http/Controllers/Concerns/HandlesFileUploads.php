<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

trait HandlesFileUploads
{
    /**
     * Store uploaded files on the public disk and attach them to the given model (Task or TaskUpdate).
     *
     * @param  UploadedFile[]|null  $files
     */
    protected function storeAttachments(?array $files, Model $owner, int $projectId, int $userId): void
    {
        // Denormalise the owning task id so the Files module can filter by task.
        $taskId = $owner instanceof Task ? $owner->id : ($owner instanceof TaskUpdate ? $owner->task_id : null);

        foreach ($files ?? [] as $file) {
            $path = $file->store('attachments', 'public');

            $owner->attachments()->create([
                'project_id' => $projectId,
                'task_id' => $taskId,
                'user_id' => $userId,
                'original_name' => $file->getClientOriginalName(),
                'path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}
