<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // GET /api/users — used for the Users page and member/assignee pickers.
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            })
            ->orderBy('name')
            ->paginate($request->integer('per_page', 50));

        return UserResource::collection($users);
    }

    // POST /api/users — admin only (enforced by StoreUserRequest::authorize).
    public function store(StoreUserRequest $request)
    {
        $user = User::create($request->validated());

        return new UserResource($user);
    }

    // PUT /api/users/{user} — admin only.
    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        // Drop a blank password so it isn't overwritten with an empty hash.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->update($data);

        return new UserResource($user);
    }
}
