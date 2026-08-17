<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use RajuBepary\LaravelModule\Support\ModuleManager;
use RajuBepary\LaravelModule\Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

/**
 * Fresh module manager: boot may have cached a fail-open active list
 * before the modules table existed, so drop the singleton first.
 */
function modules(): ModuleManager
{
    app()->forgetInstance(ModuleManager::class);

    return app(ModuleManager::class);
}
