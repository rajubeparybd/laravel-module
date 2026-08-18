<?php

namespace RajuBepary\LaravelModule;

use Illuminate\Support\Facades\Blade;
use RajuBepary\LaravelModule\Commands\ModuleActivateCommand;
use RajuBepary\LaravelModule\Commands\ModuleDeactivateCommand;
use RajuBepary\LaravelModule\Commands\ModuleInstallCommand;
use RajuBepary\LaravelModule\Commands\ModuleListCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeControllerCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeMigrationCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeMiddlewareCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeModelCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeRequestCommand;
use RajuBepary\LaravelModule\Commands\ModuleMakeSeederCommand;
use RajuBepary\LaravelModule\Commands\ModuleSetupCommand;
use RajuBepary\LaravelModule\Commands\ModuleUninstallCommand;
use RajuBepary\LaravelModule\Hooks\HookManager;
use RajuBepary\LaravelModule\Support\ModuleManager;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelModuleServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-module')
            ->hasConfigFile('laravel-module')
            ->hasViews()
            ->hasRoute('web')
            ->hasCommands([
                ModuleListCommand::class,
                ModuleInstallCommand::class,
                ModuleActivateCommand::class,
                ModuleDeactivateCommand::class,
                ModuleUninstallCommand::class,
                ModuleSetupCommand::class,
                ModuleMakeCommand::class,
                ModuleMakeControllerCommand::class,
                ModuleMakeModelCommand::class,
                ModuleMakeRequestCommand::class,
                ModuleMakeMiddlewareCommand::class,
                ModuleMakeMigrationCommand::class,
                ModuleMakeSeederCommand::class,
            ]);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(HookManager::class);
        $this->app->singleton(ModuleManager::class);
    }

    public function packageBooted(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-module');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->registerBladeDirectives();
        $this->registerNavigation();
        $this->registerActiveModules();
    }

    protected function registerNavigation(): void
    {
        if (! config('laravel-module.navigation.enabled', true)) {
            return;
        }

        $places = config('laravel-module.navigation.places', []);

        foreach ($places as $place) {
            $filter = $place['filter'] ?? null;

            if ($filter) {
                add_filter($filter, function (array $items): array {
                    $items[] = ['label' => 'Modules', 'route' => 'modules.index'];

                    return $items;
                }, $place['priority'] ?? 10);
            }
        }
    }

    protected function registerActiveModules(): void
    {
        $manager = $this->app->make(ModuleManager::class);

        // Wrap in try-catch in case database/tables do not exist during early boot or artisan commands
        try {
            $active = $manager->activeSlugs();
        } catch (\Throwable $e) {
            $active = [];
        }

        foreach ($manager->discover() as $slug => $manifest) {
            if (in_array($slug, $active, true)) {
                $this->app->register($manifest->provider);
            }
        }
    }

    protected function registerBladeDirectives(): void
    {
        Blade::directive('do_action', function (string $expression): string {
            return "<?php app(\\RajuBepary\\LaravelModule\\Hooks\\HookManager::class)->doAction({$expression}); ?>";
        });

        Blade::directive('apply_filters', function (string $expression): string {
            return "<?php echo app(\\RajuBepary\\LaravelModule\\Hooks\\HookManager::class)->applyFilters({$expression}); ?>";
        });
    }
}
