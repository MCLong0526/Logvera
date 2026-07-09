<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProjectMemberController extends Controller
{
    // GET /api/projects/{project}/members
    public function index(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        return UserResource::collection($project->members()->get());
    }

    // POST /api/projects/{project}/members  { user_id }
    public function store(Request $request, Project $project)
    {
        $this->authorize('manageMembers', $project);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if ($project->members()->where('users.id', $data['user_id'])->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['User is already a member of this project.'],
            ]);
        }

        $project->members()->attach($data['user_id'], ['role' => 'member']);

        return UserResource::collection($project->members()->get());
    }

    // DELETE /api/projects/{project}/members/{user}
    public function destroy(Request $request, Project $project, User $user)
    {
        $this->authorize('manageMembers', $project);

        if ($project->owner_id === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['The project owner cannot be removed.'],
            ]);
        }

        $project->members()->detach($user->id);

        return response()->json(['message' => 'Member removed.']);
    }
}
