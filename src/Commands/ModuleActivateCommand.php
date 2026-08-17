<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use RajuBepary\LaravelModule\Support\ModuleManager;
use RuntimeException;

class ModuleActivateCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate an installed module (boots it on the next request)';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':module:activate {slug}';
        parent::__construct();
    }

    public function handle(ModuleManager $modules): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $modules->activate($slug);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$slug}] activated.");

        return self::SUCCESS;
    }
}
