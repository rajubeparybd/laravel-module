<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

abstract class BaseModuleMakeCommand extends Command
{
    /**
     * Get the module name from argument or detect from current path.
     */
    protected function getModuleName(): string
    {
        $module = $this->option('module');

        if ($module) {
            $studlyName = Str::studly($module);
            $modulesPath = config('laravel-module.path', app_path('Modules'));
            $modulePath = $modulesPath.'/'.$studlyName;

            if (! File::exists($modulePath)) {
                $this->error("Module [{$module}] does not exist.");
                throw new RuntimeException("Module [{$module}] does not exist.");
            }

            if (! File::exists($modulePath.'/module.json')) {
                $this->error("Directory [{$module}] is not a valid module.");
                throw new RuntimeException("Directory [{$module}] is not a valid module.");
            }

            return $studlyName;
        }

        // Auto-detect from current working directory
        $cwd = getcwd();
        $modulesPath = config('laravel-module.path', app_path('Modules'));
        $modulesPath = str_replace('/', DIRECTORY_SEPARATOR, $modulesPath);
        $modulesPath = str_replace('\\', DIRECTORY_SEPARATOR, $modulesPath);

        if (str_contains($cwd, DIRECTORY_SEPARATOR.'Modules'.DIRECTORY_SEPARATOR)) {
            $parts = explode(DIRECTORY_SEPARATOR, $cwd);
            $moduleIndex = array_search('Modules', $parts);

            if ($moduleIndex !== false && isset($parts[$moduleIndex + 1])) {
                $detectedModule = $parts[$moduleIndex + 1];
                $modulePath = $modulesPath.DIRECTORY_SEPARATOR.$detectedModule;

                if (File::exists($modulePath.'/module.json')) {
                    return $detectedModule;
                }
            }
        }

        $this->error('Unable to detect module. Use --module= option or run from within a module directory.');
        throw new RuntimeException('Unable to detect module. Use --module= option or run from within a module directory.');
    }

    /**
     * Get the target module path.
     */
    protected function getModulePath(string $module): string
    {
        $modulesPath = config('laravel-module.path', app_path('Modules'));
        return $modulesPath.'/'.$module;
    }

    /**
     * Build namespace for module class.
     */
    protected function buildNamespace(string $module, string $suffix = ''): string
    {
        $namespace = 'Modules\\'.$module;

        if ($suffix) {
            $namespace .= '\\'.trim($suffix, '\\');
        }

        return $namespace;
    }

    /**
     * Ensure directory exists.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! File::isDirectory($path)) {
            File::makeDirectory($path, 0755, true);
        }
    }

    /**
     * Build class name from input.
     */
    protected function buildClassName(string $name, string $suffix = ''): string
    {
        $class = Str::studly($name);

        if ($suffix && ! str_ends_with($class, $suffix)) {
            $class .= $suffix;
        }

        return $class;
    }

    /**
     * Get stub content.
     */
    protected function getStub(string $stubName): string
    {
        $stubPath = dirname(__DIR__, 2).'/stubs/'.$stubName.'.stub';

        if (! File::exists($stubPath)) {
            throw new RuntimeException("Stub file not found: {$stubPath}");
        }

        return File::get($stubPath);
    }

    /**
     * Replace placeholder in stub content.
     */
    protected function replacePlaceholders(string $stub, array $placeholders): string
    {
        foreach ($placeholders as $key => $value) {
            $stub = str_replace('{{ '.$key.' }}', $value, $stub);
        }

        return $stub;
    }

    /**
     * Generate file from stub.
     */
    protected function generateFile(string $path, string $stub, array $placeholders): void
    {
        $content = $this->replacePlaceholders($stub, $placeholders);
        File::put($path, $content);
    }

    /**
     * Get the module key (slug) from studly name.
     */
    protected function getModuleKey(string $studlyName): string
    {
        return Str::snake($studlyName, '-');
    }

    abstract public function handle(): int;
}
