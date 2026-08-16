<?php

namespace RajuBepary\LaravelModule;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use RajuBepary\LaravelModule\Commands\LaravelModuleCommand;

class LaravelModuleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-module')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_laravel_module_table')
            ->hasCommand(LaravelModuleCommand::class);
    }
}
