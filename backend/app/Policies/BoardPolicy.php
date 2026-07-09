<?php

namespace App\Policies;

use App\Models\Board;
use App\Models\User;

class BoardPolicy
{
    // Members can view the board and work with its cards.
    public function view(User $user, Board $board): bool
    {
        return $board->hasMember($user);
    }

    // Only the owner may delete the board.
    public function delete(User $user, Board $board): bool
    {
        return $board->owner_id === $user->id;
    }
}
