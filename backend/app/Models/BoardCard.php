<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardCard extends Model
{
    public const STAGES = ['assigned', 'in_progress', 'closed'];

    protected $fillable = ['board_id', 'title', 'stage', 'position', 'created_by'];

    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
