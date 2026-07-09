<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // GET /api/dashboard
    public function index(Request $request)
    {
        $user = $request->user();
        $today = now()->toDateString();

        // Base query: tasks in projects the user belongs to.
        $tasks = fn () => Task::whereHas('project.members', fn ($q) => $q->where('users.id', $user->id));

        $cards = [
            'my_projects' => Project::whereHas('members', fn ($q) => $q->where('users.id', $user->id))->count(),
            'todays_tasks' => $tasks()->whereDate('target_date', $today)->count(),
            'overdue_tasks' => $tasks()->whereDate('target_date', '<', $today)
                ->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completed_today' => $tasks()->where('status', 'completed')->whereDate('updated_at', $today)->count(),
        ];

        $recentlyUpdated = $tasks()->with(['assignees', 'project'])
            ->latest('updated_at')->limit(6)->get();

        $charts = [
            'by_status' => $tasks()->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')->pluck('total', 'status'),
            'by_progress' => [
                '0-25' => $tasks()->whereBetween('progress', [0, 25])->count(),
                '26-50' => $tasks()->whereBetween('progress', [26, 50])->count(),
                '51-75' => $tasks()->whereBetween('progress', [51, 75])->count(),
                '76-100' => $tasks()->whereBetween('progress', [76, 100])->count(),
            ],
            'by_project' => Project::whereHas('members', fn ($q) => $q->where('users.id', $user->id))
                ->withCount('tasks')->get()->pluck('tasks_count', 'name'),
        ];

        return response()->json([
            'cards' => $cards,
            'recently_updated' => TaskResource::collection($recentlyUpdated),
            'charts' => $charts,
        ]);
    }
}
