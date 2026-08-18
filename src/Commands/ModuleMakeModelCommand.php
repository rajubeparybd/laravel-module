<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class ModuleMakeModelCommand extends BaseModuleMakeCommand
{
    protected $description = 'Create a new Eloquent model in a module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':make:model
            {name : The name of the model}
            {--module= : The name of the module}
            {--force : Force creation even if file exists}
            {--migration : Create a migration for the model}
            {--all : Generate a migration, factory, and seeder for the model}
            {--factory : Create a factory for the model}
            {--seed : Create a seeder for the model}
            {--pivot : Indicate if the model is a pivot model}';

        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->getModuleName();
        $modulePath = $this->getModulePath($module);

        $className = $this->buildClassName($name);
        $namespace = $this->buildNamespace($module, 'Models');

        $modelPath = $modulePath.'/Models/'.$className.'.php';

        if (File::exists($modelPath) && ! $this->option('force')) {
            $this->error("Model [{$className}] already exists in module [{$module}].");

            return self::FAILURE;
        }

        $this->ensureDirectoryExists($modulePath.'/Models');

        $stub = $this->getStub('model');

        $table = Str::snake(Str::pluralStudly($className));
        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $placeholders = [
            'namespace' => $namespace,
            'class' => $className,
        ];

        $this->generateFile($modelPath, $stub, $placeholders);

        $this->info("Model [{$className}] created successfully in module [{$module}].");

        // Handle related generation
        if ($this->option('migration') || $this->option('all')) {
            $this->createMigration($table, $module);
        }

        if ($this->option('factory') || $this->option('all')) {
            $this->createFactory($className, $module);
        }

        if ($this->option('seed') || $this->option('all')) {
            $this->createSeeder($className, $module);
        }

        return self::SUCCESS;
    }

    /**
     * Create a migration for the model.
     */
    private function createMigration(string $table, string $module): void
    {
        $moduleKey = $this->getModuleKey($module);
        $migrationName = 'create_'.$table.'_table';

        $process = new Process(['php', 'artisan', 'lm:make:migration', $migrationName, '--module='.$moduleKey]);
        $process->setTty(true);
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });
    }

    /**
     * Create a factory for the model.
     */
    private function createFactory(string $model, string $module): void
    {
        $moduleKey = $this->getModuleKey($module);

        $this->call('lm:make:factory', [
            'name' => $model.'Factory',
            '--module' => $moduleKey,
            '--model' => $model,
        ]);
    }

    /**
     * Create a seeder for the model.
     */
    private function createSeeder(string $model, string $module): void
    {
        $moduleKey = $this->getModuleKey($module);

        $this->call('lm:make:seeder', [
            'name' => $model.'Seeder',
            '--module' => $moduleKey,
        ]);
    }
}
