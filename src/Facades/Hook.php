<?php

namespace RajuBepary\LaravelModule\Facades;

use Illuminate\Support\Facades\Facade;
use RajuBepary\LaravelModule\Hooks\HookManager;

class Hook extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return HookManager::class;
    }
}
