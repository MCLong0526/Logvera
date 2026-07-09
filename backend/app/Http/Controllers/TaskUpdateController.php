<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesFileUploads;
use App\Http\Requests\StoreTaskUpdateRequest;
use App\Http\Resources\TaskUpdateResource;
use App\Models\Task;
use App\Notifications\ProjectActivity;
use Illuminate\Support\Facades\Notification;

class TaskUpdateController extends Controller
{
    use HandlesFileUploads;

    // GET /api/tasks/{task}/updates
    public function index(Task $task)
    {
        $this->authorize('view', $task);

        return TaskUpdateResource::collection(
            $task->updates()->with(['user', 'attachments'])->get()
        );
    }

    // POST /api/tasks/{task}/updates
    public function store(StoreTaskUpdateRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $update = $task->updates()->create([
            'user_id' => $request->user()->id,
            'description' => $request->description,
            'progress_after' => $request->progress_after,
            'status_after' => $request->status_after,
        ]);

        $this->storeAttachments($request->file('attachments'), $update, $task->project_id, $request->user()->id);

        // Keep the task's live progress/status in sync with the latest log.
        $sync = array_filter([
            'progress' => $request->progress_after,
            'status' => $request->status_after,
        ], fn ($v) => $v !== null);

        if ($sync) {
            $task->update($sync);
        }

        $members = $task->project->members()->where('users.id', '!=', $request->user()->id)->get();
        Notification::send($members, new ProjectActivity('task_updated', $task, "Update on: {$task->title}", ));

        return new TaskUpdateResource($update->load(['user', 'attachments']));
    }
}
