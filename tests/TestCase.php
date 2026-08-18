<?php

namespace RajuBepary\LaravelModule\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use RajuBepary\LaravelModule\LaravelModuleServiceProvider;
use RajuBepary\LaravelModule\Support\ModuleManager;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        app('view')->addLocation(__DIR__.'/Fixtures/views');

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'RajuBepary\\LaravelModule\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            LaravelModuleServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('app.key', 'base64:33/j7lK0y/g3R2GjXU8vK9T3S7aL8u+kK0y/g3R2GjX=');
        config()->set('laravel-module.path', __DIR__.'/Fixtures/Modules');

        // Prevent loading test modules during testing
        $app->singleton(ModuleManager::class, function ($app) {
            return new class($app) extends ModuleManager
            {
                public function discover(): array
                {
                    $modules = parent::discover();

                    // Filter out Test* modules to prevent them from being loaded
                    return array_filter($modules, fn ($key) => ! str_starts_with($key, 'test'), ARRAY_FILTER_USE_KEY);
                }
            };
        });

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/../database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}
