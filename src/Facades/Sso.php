<?php

namespace Mds\SsoClient\Facades;

use Illuminate\Support\Facades\Facade;

class Sso extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sso';
    }
} 