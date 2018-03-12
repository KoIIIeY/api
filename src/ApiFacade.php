<?php

namespace Koiiiey\Api;

use Illuminate\Support\Facades\Facade;

class ApiFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return 'koiiiey-api';
    }
}