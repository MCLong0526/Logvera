<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    // GET /api/projects — projects the user owns or belongs to.
    public function index(Request $request)
    {
        $user = $request->user();

        $projects = Project::query()
            ->whereHas('members', fn ($q) => $q->where('users.id', $user->id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->withCount(['members', 'tasks'])
            ->with('owner')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return ProjectResource::collection($projects);
    }

    // POST /api/projects
    public function store(StoreProjectRequest $request)
    {
        $project = Project::create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
            'status' => $request->input('status', 'active'),
        ]);

        // The owner is always a member with the owner role.
        $project->members()->attach($request->user()->id, ['role' => 'owner']);

        return new ProjectResource($project->load('owner')->loadCount(['members', 'tasks']));
    }

    // GET /api/projects/{project}
    public function show(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return new ProjectResource(
            $project->load(['owner', 'members'])->loadCount(['members', 'tasks'])
        );
    }

    // PUT /api/projects/{project}
    public function update(UpdateProjectRequest $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->update($request->validated());

        return new ProjectResource($project->load('owner')->loadCount(['members', 'tasks']));
    }

    // POST /api/projects/{project}/archive — toggle archive/active.
    public function archive(Request $request, Project $project)
    {
        $this->authorize('update', $project);

        $project->update([
            'status' => $project->status === 'archived' ? 'active' : 'archived',
        ]);

        return new ProjectResource($project->load('owner'));
    }

    // DELETE /api/projects/{project}
    public function destroy(Request $request, Project $project)
    {
        $this->authorize('delete', $project);

        $project->delete();

        return response()->json(['message' => 'Project deleted.']);
    }
}
