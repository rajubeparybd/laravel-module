<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use RajuBepary\LaravelModule\Models\Module;

uses(RefreshDatabase::class);

test('modules page lists discovered modules with status and metadata', function () {
    $this->get('/modules')
        ->assertStatus(200)
        ->assertSee('Customers')
        ->assertSee('Invoices')
        ->assertSee('Version 1.0.0')
        ->assertSee('CRM Core Team');
});

test('module can be installed and activated from the page', function () {
    $this->withoutExceptionHandling();
    $this->post('/modules/customers/install')->assertRedirect('/modules');
    expect(Module::query()->where('slug', 'customers')->first()->is_active)->toBeFalse();

    $this->post('/modules/customers/activate')->assertRedirect('/modules');

    $page = $this->get('/modules');
    $page->assertSee('Active');

    expect(Module::query()->where('slug', 'customers')->first()->is_active)->toBeTrue();
});

test('module can be deactivated and uninstalled from the page', function () {
    $this->post('/modules/invoices/install');
    $this->post('/modules/invoices/activate');
    $this->post('/modules/invoices/deactivate')->assertRedirect('/modules');
    $this->post('/modules/invoices/uninstall')->assertRedirect('/modules');

    expect(Module::query()->where('slug', 'invoices')->exists())->toBeFalse();

    $this->get('/modules')->assertSee('Not-installed');
});

test('uninstalling an active module shows an error instead', function () {
    $this->post('/modules/customers/install');
    $this->post('/modules/customers/activate');
    $this->post('/modules/invoices/install');
    $this->post('/modules/invoices/activate');

    $this->post('/modules/invoices/uninstall')
        ->assertRedirect('/modules')
        ->assertSessionHas('error', 'Module [invoices] must be deactivated before uninstalling.');

    expect(Module::query()->where('slug', 'invoices')->first()->is_active)->toBeTrue();
});

test('module management link appears in top navigation and sidebar', function () {
    $page = $this->get('/modules');

    $page->assertSee(route('modules.index'));
});

test('unknown module action reports an error', function () {
    $this->post('/modules/nope/install')
        ->assertRedirect('/modules')
        ->assertSessionHas('error');
});
