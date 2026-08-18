<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Support\Facades\File;

class ModuleMakeRequestCommand extends BaseModuleMakeCommand
{
    protected $description = 'Create a new form request in a module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':make:request
            {name : The name of the request}
            {--module= : The name of the module}
            {--force : Force creation even if file exists}';

        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->getModuleName();
        $modulePath = $this->getModulePath($module);

        $className = $this->buildClassName($name, 'Request');
        $namespace = $this->buildNamespace($module, 'Http\Requests');

        $requestPath = $modulePath.'/Http/Requests/'.$className.'.php';

        if (File::exists($requestPath) && ! $this->option('force')) {
            $this->error("Request [{$className}] already exists in module [{$module}].");

            return self::FAILURE;
        }

        $this->ensureDirectoryExists($modulePath.'/Http/Requests');

        $stub = $this->getStub('request');

        $placeholders = [
            'namespace' => $namespace,
            'class' => $className,
        ];

        $this->generateFile($requestPath, $stub, $placeholders);

        $this->info("Request [{$className}] created successfully in module [{$module}].");

        return self::SUCCESS;
    }
}
