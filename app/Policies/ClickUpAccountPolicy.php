<?php

namespace App\Policies;

use App\Models\ClickUpAccount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClickUpAccountPolicy
{
    use HandlesAuthorization;

    public function view(User $user, ClickUpAccount $account)
    {
        return $user->id === $account->user_id;
    }

    public function update(User $user, ClickUpAccount $account)
    {
        return $user->id === $account->user_id;
    }

    public function delete(User $user, ClickUpAccount $account)
    {
        return $user->id === $account->user_id;
    }
}