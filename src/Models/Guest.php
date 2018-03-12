<?php

namespace App;

use App\User;
/**
 * Class Guest
 *
 * @package app
 * @property-write mixed $password
 * @property mixed $type
 * @property-write mixed $profile
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $videos
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Album[] $albums
 * @mixin \Eloquent
 * @property-read \App\Education $education
 * @method static \Illuminate\Database\Query\Builder|\App\User whereSocial($type, $id)
 */
class Guest extends User
{
    /**
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        throw new \Exception('Save guest is impossible');
    }
}