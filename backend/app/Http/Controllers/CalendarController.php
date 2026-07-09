<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TaskUpdate;
use Illuminate\Http\Request;

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
            ->with(['user:id,name', 'task:id,title,project_id'])
            ->latest()
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'date' => $u->created_at->toDateString(),
                'description' => $u->description,
                'task' => $u->task ? ['id' => $u->task->id, 'title' => $u->task->title] : null,
                'user' => $u->user ? ['id' => $u->user->id, 'name' => $u->user->name] : null,
            ]);

        $projects = Project::query()
            ->when(! $user->is_admin, fn ($q) => $q->whereHas('members', fn ($m) => $m->where('users.id', $user->id)))
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->get(['id', 'name', 'due_date'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'due_date' => $p->due_date?->toDateString(),
            ]);

        return response()->json(['logs' => $logs, 'projects' => $projects]);
    }
}
