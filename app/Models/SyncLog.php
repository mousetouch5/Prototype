<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'sync_configuration_id',
        'status',
        'tasks_synced',
        'tasks_created',
        'tasks_updated',
        'tasks_failed',
        'error_details',
        'sync_summary',
        'started_at',
        'completed_at',
        'duration_seconds',
    ];

    protected $casts = [
        'error_details' => 'array',
        'sync_summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';

    public function syncConfiguration(): BelongsTo
    {
        return $this->belongsTo(SyncConfiguration::class);
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $summary = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
            'sync_summary' => $summary,
        ]);
    }

    public function markAsFailed(string $error, array $details = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'duration_seconds' => $this->started_at ? now()->diffInSeconds($this->started_at) : 0,
            'error_details' => array_merge(['message' => $error], $details),
        ]);
    }

    public function incrementTasksCreated(int $count = 1): void
    {
        $this->increment('tasks_created', $count);
        $this->increment('tasks_synced', $count);
    }

    public function incrementTasksUpdated(int $count = 1): void
    {
        $this->increment('tasks_updated', $count);
        $this->increment('tasks_synced', $count);
    }

    public function incrementTasksFailed(int $count = 1): void
    {
        $this->increment('tasks_failed', $count);
    }
}