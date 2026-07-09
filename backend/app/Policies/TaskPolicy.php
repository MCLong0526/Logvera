<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    // Any project member may view tasks.
    public function view(User $user, Task $task): bool
    {
        return $task->project->hasMember($user);
    }

    // Owner may update any task; members may update tasks they created or are assigned to.
    public function update(User $user, Task $task): bool
    {
        if ($task->project->owner_id === $user->id) {
            return true;
        }

        return $task->project->hasMember($user)
            && ($task->created_by === $user->id || $task->assignees()->whereKey($user->id)->exists());
    }

    // Owner or the task creator may delete.
    public function delete(User $user, Task $task): bool
    {
        return $task->project->owner_id === $user->id
            || $task->created_by === $user->id;
    }
}
