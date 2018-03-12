<?php

namespace Koiiiey\Api\Policies;

use App\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Class BasePolicy
 * @package App\Policies
 */
class BasePolicy
{

    public function isAuthenticated($user){
        return !$user->isGuest();
    }

    /**
     * @param User  $user
     * @param Model $entity
     * @return bool
     */
    public function create($user, $entity)
    {
        return $this->isAuthenticated($user);
    }

    /**
     * @param User  $user
     * @param Model $entity
     * @return bool
     */
    public function read($user, $entity)
    {
        return $this->isAuthenticated($user);
    }

    /**
     * @param User  $user
     * @param Model $entity
     * @return bool
     */
    public function update($user, $entity)
    {
        return $this->isAuthenticated($user);
    }

    /**
     * @param User  $user
     * @param Model $entity
     * @return bool
     */
    public function destroy($user, $entity)
    {
        return $this->isAuthenticated($user);
    }
}