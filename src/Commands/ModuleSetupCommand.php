<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use RajuBepary\LaravelModule\LaravelModuleServiceProvider;

class ModuleSetupCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the laravel-module package (publishes config and runs migrations)';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':setup';
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Publishing module config...');
        $this->call('vendor:publish', [
            '--provider' => LaravelModuleServiceProvider::class,
            '--tag' => 'module-config',
        ]);

        $this->info('Running migrations...');
        $this->call('migrate');

        $this->info('Laravel module setup complete!');

        return self::SUCCESS;
    }
}
