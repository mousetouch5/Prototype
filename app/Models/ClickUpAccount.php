<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ClickUpAccount extends Model
{
    use HasFactory;

    protected $table = 'clickup_accounts';

    protected $fillable = [
        'user_id',
        'name',
        'account_type',
        'access_token',
        'refresh_token',
        'clickup_user_id',
        'clickup_username',
        'clickup_email',
        'workspaces',
        'token_expires_at',
        'is_active',
    ];

    protected $casts = [
        'workspaces' => 'array',
        'token_expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceSyncConfigurations(): HasMany
    {
        return $this->hasMany(SyncConfiguration::class, 'source_account_id');
    }

    public function targetSyncConfigurations(): HasMany
    {
        return $this->hasMany(SyncConfiguration::class, 'target_account_id');
    }


    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function getAccessTokenAttribute($value): string
    {
        return $value ? Crypt::decryptString($value) : '';
    }

    public function setRefreshTokenAttribute($value): void
    {
        if ($value) {
            $this->attributes['refresh_token'] = Crypt::encryptString($value);
        }
    }

    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }
}