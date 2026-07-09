<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesFileUploads;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Notifications\ProjectActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class TaskController extends Controller
{
    use HandlesFileUploads;

    // GET /api/tasks — global list with search & filters (drives timeline + search page).
    public function index(Request $request)
    {
        $user = $request->user();

        $tasks = Task::query()
            ->whereHas('project.members', fn ($q) => $q->where('users.id', $user->id))
            ->when($request->filled('project_id'), fn ($q) => $q->where('project_id', $request->project_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('priority'), fn ($q) => $q->where('priority', $request->priority))
            ->when($request->filled('assigned_user_id'), fn ($q) => $q->whereHas('assignees', fn ($a) => $a->where('users.id', $request->assigned_user_id)))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->when($request->filled('date'), fn ($q) => $q->whereDate('target_date', $request->date))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('target_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('target_date', '<=', $request->date_to))
            ->with(['assignees', 'creator', 'project'])
            ->withCount('updates')
            ->orderByDesc('target_date')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return TaskResource::collection($tasks);
    }

    // POST /api/projects/{project}/tasks
    public function store(StoreTaskRequest $request, Project $project)
    {
        $this->authorize('view', $project); // any member may create tasks

        $task = $project->tasks()->create([
            ...$request->safe()->except(['attachments', 'assigned_user_ids']),
            'progress' => $request->input('progress', 0),
            'created_by' => $request->user()->id,
        ]);

        $task->assignees()->sync($request->input('assigned_user_ids', []));

        $this->storeAttachments($request->file('attachments'), $task, $project->id, $request->user()->id);

        $this->notifyMembers($task, 'task_created', "New task: {$task->title}", $request->user());

        return new TaskResource($task->load(['assignees', 'creator', 'attachments']));
    }

    // GET /api/tasks/{task}
    public function show(Request $request, Task $task)
    {
        $this->authorize('view', $task);

        return new TaskResource($task->load([
            'assignees', 'creator', 'project',
            'updates.user', 'updates.attachments', 'attachments.user',
        ]));
    }

    // PUT /api/tasks/{task}
    public function update(UpdateTaskRequest $request, Task $task)
    {
        $this->authorize('update', $task);

        $task->update($request->safe()->except('assigned_user_ids'));

        if ($request->has('assigned_user_ids')) {
            $task->assignees()->sync($request->input('assigned_user_ids', []));
        }

        $event = $task->wasChanged('status') && $task->status === 'completed' ? 'task_completed' : 'task_updated';
        $this->notifyMembers($task, $event, "Task updated: {$task->title}", $request->user());

        return new TaskResource($task->load(['assignees', 'creator']));
    }

    // DELETE /api/tasks/{task}
    public function destroy(Request $request, Task $task)
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }

    // Notify every project member except the actor.
    private function notifyMembers(Task $task, string $event, string $message, User $actor): void
    {
        $members = $task->project->members()->where('users.id', '!=', $actor->id)->get();

        Notification::send($members, new ProjectActivity($event, $task, $message));
    }
}
