<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use RajuBepary\LaravelModule\Support\ModuleManager;
use RuntimeException;

class ModuleDeactivateCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deactivate an active module (stops booting its provider)';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':module:deactivate {slug}';
        parent::__construct();
    }

    public function handle(ModuleManager $modules): int
    {
        $slug = (string) $this->argument('slug');

        try {
            $modules->deactivate($slug);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Module [{$slug}] deactivated.");

        return self::SUCCESS;
    }
}
