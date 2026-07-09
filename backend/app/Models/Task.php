<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = [
        'bug', 'change_request', 'development', 'enhancement', 'idea', 'maintenance',
        'others', 'quality_assurance', 'release', 'research_and_do', 'unit_testing',
        'update', 'website_migration',
    ];
    public const PRIORITIES = ['low', 'medium', 'high', 'critical'];
    public const STATUSES = ['not_started', 'in_progress', 'on_hold', 'completed', 'cancelled'];

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'progress',
        'target_date',
        'target_time',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'progress' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'task_assignees')->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(TaskUpdate::class)->latest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
