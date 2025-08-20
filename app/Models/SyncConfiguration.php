<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SyncConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'source_account_id',
        'source_platform',
        'source_workspace_id',
        'source_space_id',
        'source_folder_id',
        'source_list_id',
        'target_account_id',
        'target_platform',
        'target_workspace_id',
        'target_space_id',
        'target_folder_id',
        'target_list_id',
        'sync_options',
        'field_mapping',
        'status_mapping',
        'user_mapping',
        'sync_direction',
        'conflict_resolution',
        'sync_attachments',
        'sync_comments',
        'sync_custom_fields',
        'schedule_type',
        'schedule_interval',
        'schedule_cron',
        'last_sync_at',
        'next_sync_at',
        'is_active',
    ];

    protected $casts = [
        'sync_options' => 'array',
        'field_mapping' => 'array',
        'status_mapping' => 'array',
        'user_mapping' => 'array',
        'sync_attachments' => 'boolean',
        'sync_comments' => 'boolean',
        'sync_custom_fields' => 'boolean',
        'last_sync_at' => 'datetime',
        'next_sync_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceAccount(): BelongsTo
    {
        if ($this->source_platform === 'monday') {
            return $this->belongsTo(MondayAccount::class, 'source_account_id');
        }
        return $this->belongsTo(ClickUpAccount::class, 'source_account_id');
    }

    public function targetAccount(): BelongsTo
    {
        if ($this->target_platform === 'monday') {
            return $this->belongsTo(MondayAccount::class, 'target_account_id');
        }
        return $this->belongsTo(ClickUpAccount::class, 'target_account_id');
    }

    public function getSourceServiceAttribute()
    {
        $account = $this->sourceAccount;
        if ($this->source_platform === 'monday') {
            return new \App\Services\MondayService($account);
        }
        return new \App\Services\ClickUpService($account);
    }

    public function getTargetServiceAttribute()
    {
        $account = $this->targetAccount;
        if ($this->target_platform === 'monday') {
            return new \App\Services\MondayService($account);
        }
        return new \App\Services\ClickUpService($account);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(SyncLog::class);
    }

    public function getLatestSyncLog()
    {
        return $this->syncLogs()->latest()->first();
    }

    public function isScheduled(): bool
    {
        return $this->schedule_type !== 'manual';
    }

    public function isDue(): bool
    {
        if (!$this->isScheduled()) {
            return false;
        }

        return $this->next_sync_at && $this->next_sync_at->isPast();
    }

    public function calculateNextSyncTime(): void
    {
        if ($this->schedule_type === 'interval' && $this->schedule_interval) {
            $this->next_sync_at = now()->addMinutes($this->schedule_interval);
        } elseif ($this->schedule_type === 'cron' && $this->schedule_cron) {
            // Implement cron expression parsing here
            // For now, default to 24 hours
            $this->next_sync_at = now()->addDay();
        }
    }
}