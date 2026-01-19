<?php

namespace App\Policies;

use App\Models\Digest;
use App\Models\User;

class DigestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Digest $digest): bool
    {
        return $user->id === $digest->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Digest $digest): bool
    {
        return $user->id === $digest->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Digest $digest): bool
    {
        return $user->id === $digest->user_id;
    }

    /**
     * Determine whether the user can toggle the model's enabled status.
     */
    public function toggle(User $user, Digest $digest): bool
    {
        return $user->id === $digest->user_id;
    }
}
