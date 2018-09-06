<?php

namespace Mont4\LaravelMaker\Facades;

use Illuminate\Support\Facades\Facade;

class LaravelMaker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laravelmaker';
    }
}
