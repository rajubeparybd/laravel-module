<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ModuleMakeCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':module:make {slug}';
        parent::__construct();
    }

    public function handle(): int
    {
        $slug = (string) $this->argument('slug');
        $studlyName = Str::studly($slug);

        $modulesPath = config('laravel-module.path', app_path('Modules'));
        $modulePath = $modulesPath.'/'.$studlyName;

        if (File::exists($modulePath)) {
            $this->error("Module [{$slug}] already exists at {$modulePath}.");

            return self::FAILURE;
        }

        // Create directories
        $directories = [
            $modulePath,
            $modulePath.'/routes',
            $modulePath.'/config',
            $modulePath.'/Http/Controllers',
            $modulePath.'/resources/views',
        ];

        foreach ($directories as $directory) {
            File::makeDirectory($directory, 0755, true);
        }

        $authorName = config('laravel-module.author.name', 'Your Name');
        $authorEmail = config('laravel-module.author.email', 'your-email@example.com');
        $authorString = $authorName.' <'.$authorEmail.'>';
        $moduleName = str_replace('-', ' ', Str::title($slug));

        // Create module.json
        $moduleJson = [
            'id' => $slug,
            'name' => $moduleName,
            'description' => 'Description for '.$slug,
            'version' => '1.0.0',
            'author' => trim($authorString),
            'provider' => "Modules\\{$studlyName}\\{$studlyName}ServiceProvider",
            'requires' => [],
            'protected' => false,
        ];

        File::put(
            $modulePath.'/module.json',
            json_encode($moduleJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        // Create Service Provider
        $providerStub = <<<PHP
<?php

namespace Modules\\{$studlyName};

use Illuminate\Support\ServiceProvider;

class {$studlyName}ServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        \$this->mergeConfigFrom(__DIR__.'/config/{$slug}.php', '{$slug}');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \$this->loadRoutesFrom(__DIR__.'/routes/web.php');
        \$this->loadRoutesFrom(__DIR__.'/routes/api.php');
        \$this->loadViewsFrom(__DIR__.'/resources/views', '{$slug}');

        \$this->registerNavigation();
    }

    /**
     * Inject this module into the shared navigation surfaces.
     */
    protected function registerNavigation(): void
    {
        add_filter('admin.topnav', function (array \$items): array {
            \$items[] = ['label' => '{$moduleName}', 'route' => '{$slug}.index'];

            return \$items;
        });

        add_filter('admin.navigation', function (array \$items): array {
            \$items[] = ['label' => '{$moduleName}', 'route' => '{$slug}.index'];

            return \$items;
        });
    }

    /**
     * Executed when the module is activated.
     */
    public function activate(): void
    {
        //
    }

    /**
     * Executed when the module is deactivated.
     */
    public function deactivate(): void
    {
        //
    }

    /**
     * Executed when the module is uninstalled.
     */
    public function uninstall(): void
    {
        //
    }
}
PHP;

        File::put($modulePath."/{$studlyName}ServiceProvider.php", $providerStub);

        // Create Base Controller
        $baseControllerStub = <<<PHP
<?php

namespace Modules\\{$studlyName}\\Http\\Controllers;

use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    //
}
PHP;

        File::put($modulePath.'/Http/Controllers/BaseController.php', $baseControllerStub);

        // Create Main Controller
        $controllerStub = <<<PHP
<?php

namespace Modules\\{$studlyName}\\Http\\Controllers;

use Illuminate\Http\Request;

class {$studlyName}Controller extends BaseController
{
    public function index()
    {
        return view('{$slug}::index');
    }
}
PHP;

        File::put($modulePath."/Http/Controllers/{$studlyName}Controller.php", $controllerStub);

        // Create routes
        $webRouteStub = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use Modules\\{$studlyName}\\Http\\Controllers\\{$studlyName}Controller;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module.
|
*/

Route::get('/{$slug}', [{$studlyName}Controller::class, 'index'])->name('{$slug}.index');
PHP;

        File::put($modulePath.'/routes/web.php', $webRouteStub);

        $apiRouteStub = <<<PHP
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your module.
|
*/

// Route::get('/{$slug}', function () {
//     return response()->json(['message' => 'Hello from {$slug} module!']);
// });
PHP;

        File::put($modulePath.'/routes/api.php', $apiRouteStub);

        // Create config
        $configStub = <<<PHP
<?php

return [
    'name' => '{$studlyName}'
];
PHP;

        File::put($modulePath."/config/{$slug}.php", $configStub);

        // Create index view
        $viewStub = <<<HTML
<h1>Hello from {$studlyName} Module!</h1>
<p>
    This view is loaded from module: {!! config('{$slug}.name') !!}
</p>
HTML;

        File::put($modulePath.'/resources/views/index.blade.php', $viewStub);

        $this->info("Module [{$slug}] created successfully at {$modulePath}.");

        $composerPath = base_path('composer.json');
        if (File::exists($composerPath)) {
            $composerConfig = json_decode(File::get($composerPath), true);
            $psr4 = $composerConfig['autoload']['psr-4'] ?? [];
            if (! isset($psr4['Modules\\'])) {
                $composerConfig['autoload']['psr-4']['Modules\\'] = 'app/Modules/';
                File::put($composerPath, json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

                $this->info('Added "Modules\\" to autoload.psr-4 in composer.json.');
                $this->info('Running composer dump-autoload...');

                $process = Process::fromShellCommandline('composer dump-autoload', base_path());
                $process->run(function ($type, $buffer) {
                    $this->output->write($buffer);
                });
            }
        }

        return self::SUCCESS;
    }
}
