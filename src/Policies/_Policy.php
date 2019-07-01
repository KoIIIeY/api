<?php

namespace App\Policies;

use App\User as Entity;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Class UserPolicy
 * @package App\Policies
 */
class _Policy
{
    use HandlesAuthorization;

    /**
     * @param User $user
     * @param User $entity
     * @return bool
     */
    public function create(User $user, Entity $entity)
    {
        return !$user->isGuest();
    }

    /**
     * @param User $user
     * @param User $entity
     * @return bool
     */
    public function read(User $user, Entity $entity)
    {
        return !$user->isGuest();
    }

    /**
     * @param User $user
     * @param User $entity
     * @return bool
     */
    public function update(User $user, Entity $entity)
    {
        return !$user->isGuest() && $user->user_id == $entity->user_id;
    }

    /**
     * @param User $user
     * @param User $entity
     * @return bool
     */
    public function destroy(User $user, Entity $entity)
    {
        return false;
    }
}
