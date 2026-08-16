<?php

namespace RajuBepary\LaravelModule\Commands;

use Illuminate\Console\Command;

class LaravelModuleCommand extends Command
{
    public $signature = 'laravel-module';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
