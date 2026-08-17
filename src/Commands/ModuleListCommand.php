<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;
use RajuBepary\LaravelModule\Support\ModuleManager;

class ModuleListCommand extends Command
{
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all modules with their lifecycle status';

    public function __construct()
    {
        $this->signature = config('laravel-module.command_prefix', 'lm').':module:list';
        parent::__construct();
    }

    public function handle(ModuleManager $modules): int
    {
        $rows = [];

        foreach ($modules->discover() as $slug => $manifest) {
            $rows[] = [
                $slug,
                $manifest->name,
                $manifest->version,
                $modules->status($slug),
                $manifest->description,
            ];
        }

        $this->table(['Slug', 'Name', 'Version', 'Status', 'Description'], $rows);

        return self::SUCCESS;
    }
}
