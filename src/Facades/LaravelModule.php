<?php

namespace RajuBepary\LaravelModule\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \RajuBepary\LaravelModule\LaravelModule
 */
class LaravelModule extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RajuBepary\LaravelModule\LaravelModule::class;
    }
}
