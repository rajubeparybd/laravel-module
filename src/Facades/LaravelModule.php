<?php

namespace RajuBepary\LaravelModule\Facades;

use Illuminate\Support\Facades\Facade;
use RajuBepary\LaravelModule\Support\ModuleManager;

/**
 * @see ModuleManager
 */
class LaravelModule extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ModuleManager::class;
    }
}
