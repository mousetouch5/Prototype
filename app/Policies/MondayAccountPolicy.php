<?php

namespace App\Policies;

use App\Models\MondayAccount;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MondayAccountPolicy
{
    use HandlesAuthorization;

    public function view(User $user, MondayAccount $account)
    {
        return $user->id === $account->user_id;
    }

    public function update(User $user, MondayAccount $account)
    {
        return $user->id === $account->user_id;
    }

    public function delete(User $user, MondayAccount $account)
    {
        return $user->id === $account->user_id;
    }
}