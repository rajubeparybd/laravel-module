<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ModuleMakeMigrationCommand extends BaseModuleMakeCommand
{
    protected $description = 'Create a new migration in a module';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':make:migration
            {name : The name of the migration}
            {--module= : The name of the module}
            {--force : Force creation even if file exists}
            {--create= : The table to be created}
            {--table= : The table to migrate}
            {--path= : The location where the migration file should be created}';

        parent::__construct();
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $module = $this->getModuleName();
        $modulePath = $this->getModulePath($module);

        // Build migration file name
        $timestamp = date('Y_m_d_His');
        $migrationName = $this->buildClassName($name);
        $fileName = $timestamp.'_'.$migrationName.'.php';

        $migrationPath = $modulePath.'/database/migrations';

        if ($this->option('path')) {
            $migrationPath = $this->option('path');
        }

        $fullPath = $migrationPath.'/'.$fileName;

        if (File::exists($fullPath) && ! $this->option('force')) {
            $this->error("Migration [{$migrationName}] already exists in module [{$module}].");

            return self::FAILURE;
        }

        $this->ensureDirectoryExists($migrationPath);

        $stub = $this->getStub('migration');

        // Determine table name
        $table = $this->getTableName($migrationName);

        $placeholders = [
            'table' => $table,
        ];

        $this->generateFile($fullPath, $stub, $placeholders);

        $this->info("Migration [{$migrationName}] created successfully in module [{$module}].");
        $this->info("File: {$fullPath}");

        return self::SUCCESS;
    }

    /**
     * Get the table name for the migration.
     */
    private function getTableName(string $migrationName): string
    {
        if ($this->option('create')) {
            return $this->option('create');
        }

        if ($this->option('table')) {
            return $this->option('table');
        }

        // Try to extract table name from migration name
        // e.g., create_users_table -> users
        if (preg_match('/create_(.+?)_table/i', $migrationName, $matches)) {
            return $matches[1];
        }

        // Default: use the migration name
        return Str::snake($migrationName);
    }
}
