<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use App\Models\TaskUpdate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CalendarController extends Controller
{
    // GET /api/calendar?month=YYYY-MM
    // Returns user activity logs (task updates) and project due dates for the month,
    // scoped to the user's projects — admins see everything.
    public function index(Request $request)
    {
        $user = $request->user();
        $month = $request->filled('month')
            ? \Illuminate\Support\Carbon::parse($request->month.'-01')
            : now();
        $start = $month->copy()->startOfMonth()->startOfDay();
        $end = $month->copy()->endOfMonth()->endOfDay();

        $logs = TaskUpdate::query()
            ->when(! $user->is_admin, fn ($q) => $q->whereHas('task.project.members', fn ($m) => $m->where('users.id', $user->id)))
            ->whereBetween('created_at', [$start, $end])
            ->with(['user:id,name,avatar', 'task:id,title,project_id', 'task.project:id,name'])
            ->latest()
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'date' => $u->created_at->toDateString(),
                'description' => $u->description,
                'task' => $u->task ? [
                    'id' => $u->task->id,
                    'title' => $u->task->title,
                    'project' => $u->task->project?->name,
                ] : null,
                'user' => $u->user ? $this->userPayload($u->user) : null,
            ]);

        // Assigned tasks due this month — one entry per assignee on the target date.
        $tasks = Task::query()
            ->when(! $user->is_admin, fn ($q) => $q->whereHas('project.members', fn ($m) => $m->where('users.id', $user->id)))
            ->whereHas('assignees')
            ->whereBetween('target_date', [$start->toDateString(), $end->toDateString()])
            ->with(['assignees:id,name,avatar', 'project:id,name'])
            ->get()
            ->flatMap(fn ($t) => $t->assignees->map(fn ($a) => [
                'id' => $t->id,
                'date' => $t->target_date->toDateString(),
                'title' => $t->title,
                'status' => $t->status,
                'project' => $t->project?->name,
                'user' => $this->userPayload($a),
            ]))
            ->values();

        $projects = Project::query()
            ->when(! $user->is_admin, fn ($q) => $q->whereHas('members', fn ($m) => $m->where('users.id', $user->id)))
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'name', 'due_date'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'due_date' => $p->due_date?->toDateString(),
            ]);

        return response()->json(['logs' => $logs, 'tasks' => $tasks, 'projects' => $projects]);
    }

    private function userPayload($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
        ];
    }
}
