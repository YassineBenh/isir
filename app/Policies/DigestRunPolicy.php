<?php

namespace App\Policies;

use App\Models\DigestRun;
use App\Models\User;

class DigestRunPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DigestRun $digestRun): bool
    {
        return $user->id === $digestRun->digest->user_id;
    }
}
