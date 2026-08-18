<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleMakeControllerCommand extends BaseModuleMakeCommand
{
    protected $description = 'Create a new controller in a module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':make:controller
            {name : The name of the controller}
            {--module= : The name of the module}
            {--force : Force creation even if file exists}
            {--api : Exclude the create and edit methods}
            {--invokable : Generate a single method, invokable controller class}
            {--singleton : Generate a singleton resource controller class}
            {--plain : Generate a plain controller class}
            {--requests : Generate the FormRequest classes for store and update}
            {--model= : The model name for resource controller}';

        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->getModuleName();
        $modulePath = $this->getModulePath($module);

        $className = $this->buildClassName($name, 'Controller');
        $namespace = $this->buildNamespace($module, 'Http\Controllers');

        $controllerPath = $modulePath.'/Http/Controllers/'.$className.'.php';

        if (File::exists($controllerPath) && ! $this->option('force')) {
            $this->error("Controller [{$className}] already exists in module [{$module}].");

            return self::FAILURE;
        }

        $this->ensureDirectoryExists($modulePath.'/Http/Controllers');

        // Determine stub type
        $stubType = $this->determineStubType();
        $stub = $this->getStub($stubType);

        // Build model name if provided
        $model = null;
        $modelNamespace = null;
        if ($this->option('model')) {
            $model = $this->buildClassName($this->option('model'));
            $modelNamespace = $this->buildNamespace($module, 'Models');
        }

        // Generate form requests if requested
        $requestClasses = [];
        if ($this->option('requests') && ! $this->option('invokable') && ! $this->option('singleton')) {
            $requestClasses = $this->generateFormRequests($module, $name);
        }

        // Replace placeholders
        $placeholders = [
            'namespace' => $namespace,
            'class' => $className,
            'model' => $model,
            'modelNamespace' => $modelNamespace,
            'modelVariable' => $model ? Str::lower($model) : null,
        ];

        // Remove model placeholders if no model provided
        if (! $model) {
            $stub = preg_replace('/use.*?Model;/m', '', $stub);
        }

        // Replace request FQCNs if generated
        if (! empty($requestClasses)) {
            $stub = str_replace('use Illuminate\Http\Request;', "use Illuminate\\Http\\Request;\n".implode("\n", array_map(fn ($r) => "use {$r};", $requestClasses)), $stub);
            $stub = str_replace('Request $request', 'StoreRequest $storeRequest, UpdateRequest $updateRequest', $stub);
            $stub = str_replace('public function store(', 'public function store(StoreRequest $storeRequest', $stub);
            $stub = str_replace('public function update(', 'public function update(UpdateRequest $updateRequest', $stub);
        }

        $this->generateFile($controllerPath, $stub, $placeholders);

        $this->info("Controller [{$className}] created successfully in module [{$module}].");

        if ($this->option('requests')) {
            foreach ($requestClasses as $requestClass) {
                $this->info("Request [{$requestClass}] created.");
            }
        }

        return self::SUCCESS;
    }

    /**
     * Determine which stub to use based on options.
     */
    private function determineStubType(): string
    {
        if ($this->option('invokable')) {
            return 'controller.invokable';
        }

        if ($this->option('singleton')) {
            return 'controller.singleton';
        }

        if ($this->option('api')) {
            return 'controller.api';
        }

        if ($this->option('plain')) {
            return 'controller.plain';
        }

        return 'controller.plain';
    }

    /**
     * Generate form request classes for store and update.
     */
    private function generateFormRequests(string $module, string $controllerName): array
    {
        $requests = [];
        $baseName = Str::studly($controllerName);
        $requestNamespace = $this->buildNamespace($module, 'Http\Requests');

        foreach (['Store', 'Update'] as $action) {
            $requestName = $action.$baseName.'Request';
            $requestPath = $this->getModulePath($module).'/Http/Requests/'.$requestName.'.php';

            $this->ensureDirectoryExists($this->getModulePath($module).'/Http/Requests');

            $stub = $this->getStub('request');
            $this->generateFile($requestPath, $stub, [
                'namespace' => $requestNamespace,
                'class' => $requestName,
            ]);

            $requests[] = $requestNamespace.'\\'.$requestName;
        }

        return $requests;
    }
}
