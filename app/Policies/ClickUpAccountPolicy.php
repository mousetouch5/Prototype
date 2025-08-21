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
        
       $result = (int) $user->id === (int) $account->user_id;
        
        \Log::info('ClickUpAccountPolicy::delete called', [
            'user_id' => $user->id,
            'user_id_type' => gettype($user->id),
            'account_user_id' => $account->user_id,
            'account_user_id_type' => gettype($account->user_id),
            'strict_match' => $user->id === $account->user_id,
            'loose_match' => $user->id == $account->user_id,
            'result' => $result
        ]);
        
        return $result;
    }
}