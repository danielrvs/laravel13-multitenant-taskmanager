<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class TaskPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }

    public function before(User $user, string $ability): ?bool
    {

        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return ! $user->isViewer();
    }

    public function update(User $user, int $id): bool
    {
        return ! $user->isViewer();
    }

    public function delete(User $user, int $id): bool
    {
        return false;
    }
}
