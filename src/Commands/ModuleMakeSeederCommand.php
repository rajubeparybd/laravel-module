<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Support\Facades\File;

class ModuleMakeSeederCommand extends BaseModuleMakeCommand
{
    protected $description = 'Create a new seeder in a module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':make:seeder
            {name : The name of the seeder}
            {--module= : The name of the module}
            {--force : Force creation even if file exists}';

        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->getModuleName();
        $modulePath = $this->getModulePath($module);

        $className = $this->buildClassName($name, 'Seeder');
        $namespace = $this->buildNamespace($module, 'database\seeders');

        $seederPath = $modulePath.'/database/seeders/'.$className.'.php';

        if (File::exists($seederPath) && ! $this->option('force')) {
            $this->error("Seeder [{$className}] already exists in module [{$module}].");

            return self::FAILURE;
        }

        $this->ensureDirectoryExists($modulePath.'/database/seeders');

        $stub = $this->getStub('seeder');

        $placeholders = [
            'namespace' => $namespace,
            'class' => $className,
        ];

        $this->generateFile($seederPath, $stub, $placeholders);

        $this->info("Seeder [{$className}] created successfully in module [{$module}].");
        $this->newLine();
        $this->warn('Remember to call this seeder from DatabaseSeeder or your module\'s service provider.');

        return self::SUCCESS;
    }
}
