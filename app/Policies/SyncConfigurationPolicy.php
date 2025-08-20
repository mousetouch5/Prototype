<?php

namespace App\Policies;

use App\Models\SyncConfiguration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SyncConfigurationPolicy
{
    use HandlesAuthorization;

    public function view(User $user, SyncConfiguration $configuration)
    {
        return $user->id === $configuration->user_id;
    }

    public function update(User $user, SyncConfiguration $configuration)
    {
        return $user->id === $configuration->user_id;
    }

    public function delete(User $user, SyncConfiguration $configuration)
    {
        return $user->id === $configuration->user_id;
    }
}