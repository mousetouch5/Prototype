<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class MondayAccount extends Model
{
    use HasFactory;

    protected $table = 'monday_accounts';

    protected $fillable = [
        'user_id',
        'name',
        'account_type',
        'access_token',
        'refresh_token',
        'monday_user_id',
        'monday_username',
        'monday_email',
        'boards',
        'token_expires_at',
        'is_active',
    ];

    protected $casts = [
        'boards' => 'array',
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
        return $this->hasMany(SyncConfiguration::class, 'source_account_id')
            ->where('source_platform', 'monday');
    }

    public function targetSyncConfigurations(): HasMany
    {
        return $this->hasMany(SyncConfiguration::class, 'target_account_id')
            ->where('target_platform', 'monday');
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