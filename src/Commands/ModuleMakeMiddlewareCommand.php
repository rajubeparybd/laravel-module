<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Support\Facades\File;

class ModuleMakeMiddlewareCommand extends BaseModuleMakeCommand
{
    protected $description = 'Create a new middleware in a module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':make:middleware
            {name : The name of the middleware}
            {--module= : The name of the module}
            {--force : Force creation even if file exists}';

        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->getModuleName();
        $modulePath = $this->getModulePath($module);

        $className = $this->buildClassName($name);
        $namespace = $this->buildNamespace($module, 'Http\Middleware');

        $middlewarePath = $modulePath.'/Http/Middleware/'.$className.'.php';

        if (File::exists($middlewarePath) && ! $this->option('force')) {
            $this->error("Middleware [{$className}] already exists in module [{$module}].");

            return self::FAILURE;
        }

        $this->ensureDirectoryExists($modulePath.'/Http/Middleware');

        $stub = $this->getStub('middleware');

        $placeholders = [
            'namespace' => $namespace,
            'class' => $className,
        ];

        $this->generateFile($middlewarePath, $stub, $placeholders);

        $this->info("Middleware [{$className}] created successfully in module [{$module}].");
        $this->newLine();
        $this->warn('Remember to register this middleware in your module\'s service provider or routes.');

        return self::SUCCESS;
    }
}
