<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use RajuBepary\LaravelModule\Support\ModuleManager;
use RuntimeException;

class ModuleInstallCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install a module (registers it in the database)';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':module:install {slug} {--activate : Activate the module right after installing}';
        parent::__construct();
    }

    public function handle(ModuleManager $modules): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $modules->install($slug);

            if ($this->option('activate')) {
                $modules->activate($slug);
            }
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $state = $this->option('activate') ? 'installed and activated' : 'installed';

        $this->info("Module [{$slug}] {$state}.");

        return self::SUCCESS;
    }
}
