<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use RajuBepary\LaravelModule\Support\ModuleManager;
use RuntimeException;

class ModuleUninstallCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall a deactivated module (runs its uninstall hook, removes the record)';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':module:uninstall {slug}';
        parent::__construct();
    }

    public function handle(ModuleManager $modules): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $modules->uninstall($slug);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$slug}] uninstalled. Module code remains on disk.");

        return self::SUCCESS;
    }
}
