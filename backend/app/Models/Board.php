<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Board extends Model
{
    protected $fillable = ['name', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    // Everyone on the board, owner included (attached on create).
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'board_members')->withTimestamps();
    }

    public function cards(): HasMany
    {
        return $this->hasMany(BoardCard::class)->orderBy('position');
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }
}
