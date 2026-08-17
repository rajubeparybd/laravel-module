<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->modulesPath = __DIR__.'/../Fixtures/TempModules';
    config()->set('laravel-module.path', $this->modulesPath);

    // Clean up before test
    if (File::exists($this->modulesPath)) {
        File::deleteDirectory($this->modulesPath);
    }
    File::makeDirectory($this->modulesPath, 0755, true);
});

afterEach(function () {
    // Clean up after test
    if (File::exists($this->modulesPath)) {
        File::deleteDirectory($this->modulesPath);
    }
});

it('can scaffold a new module', function () {
    config()->set('laravel-module.author.name', 'John Doe');
    config()->set('laravel-module.author.email', 'john@example.com');

    $this->artisan('lm:module:make', ['slug' => 'test-module'])
        ->assertSuccessful()
        ->expectsOutput('Module [test-module] created successfully at '.$this->modulesPath.'/TestModule.');

    // Assert directories exist
    expect(File::exists($this->modulesPath.'/TestModule'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/Http/Controllers'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/routes'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/config'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/resources/views'))->toBeTrue();

    // Assert files exist
    expect(File::exists($this->modulesPath.'/TestModule/module.json'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/TestModuleServiceProvider.php'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/Http/Controllers/BaseController.php'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/Http/Controllers/TestModuleController.php'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/routes/web.php'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/routes/api.php'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/config/test-module.php'))->toBeTrue();
    expect(File::exists($this->modulesPath.'/TestModule/resources/views/index.blade.php'))->toBeTrue();

    // Assert module.json contents
    $moduleJson = json_decode(File::get($this->modulesPath.'/TestModule/module.json'), true);
    expect($moduleJson['id'])->toBe('test-module');
    expect($moduleJson['name'])->toBe('Test Module');
    expect($moduleJson['author'])->toBe('John Doe <john@example.com>');
    expect($moduleJson['provider'])->toBe('Modules\TestModule\TestModuleServiceProvider');
});

it('fails if module already exists', function () {
    File::makeDirectory($this->modulesPath.'/TestModule', 0755, true);

    $this->artisan('lm:module:make', ['slug' => 'test-module'])
        ->assertFailed()
        ->expectsOutput('Module [test-module] already exists at '.$this->modulesPath.'/TestModule.');
});
