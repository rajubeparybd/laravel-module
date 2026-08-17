<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use RajuBepary\LaravelModule\Models\Module;

uses(RefreshDatabase::class);

test('manager discovers manifests for shipped modules', function () {
    $discovered = modules()->discover();

    expect($discovered)->toHaveKeys(['customers', 'invoices'])
        ->and($discovered['customers']->name)->toBe('Customers')
        ->and($discovered['invoices']->name)->toBe('Invoices');
});

test('first boot installs and activates every discovered module', function () {
    expect(modules()->activeSlugs())->toBe(['customers', 'invoices'])
        ->and(modules()->status('customers'))->toBe('active')
        ->and(modules()->status('invoices'))->toBe('active');
});

test('module can be deactivated and activated again', function () {
    modules()->activeSlugs();

    $events = [];
    add_action('module.deactivated', function ($manifest) use (&$events) {
        $events[] = 'deactivated:'.$manifest->slug;
    });
    add_action('module.activated', function ($manifest) use (&$events) {
        $events[] = 'activated:'.$manifest->slug;
    });

    modules()->deactivate('invoices');

    expect(modules()->status('invoices'))->toBe('inactive')
        ->and(modules()->isActive('invoices'))->toBeFalse()
        ->and(modules()->isActive('customers'))->toBeTrue();

    modules()->activate('invoices');

    expect(modules()->status('invoices'))->toBe('active')
        ->and($events)->toBe(['deactivated:invoices', 'activated:invoices']);
});

test('active module cannot be uninstalled directly', function () {
    modules()->activeSlugs();

    modules()->uninstall('invoices');
})->throws(RuntimeException::class, 'must be deactivated before uninstalling');

test('module can be uninstalled after deactivation', function () {
    modules()->activeSlugs();

    $uninstalled = null;
    add_action('module.uninstalled', function ($manifest) use (&$uninstalled) {
        $uninstalled = $manifest->slug;
    });

    modules()->deactivate('invoices');
    modules()->uninstall('invoices');

    expect(Module::query()->where('slug', 'invoices')->exists())->toBeFalse()
        ->and(modules()->status('invoices'))->toBe('not-installed')
        ->and($uninstalled)->toBe('invoices');
});

test('installing an already installed module fails', function () {
    modules()->activeSlugs();

    modules()->install('customers');
})->throws(RuntimeException::class, 'already installed');

test('unknown module slug fails', function () {
    modules()->install('does-not-exist');
})->throws(RuntimeException::class, 'does not exist');

test('module can be reinstalled and activated after uninstall', function () {
    modules()->activeSlugs();

    modules()->deactivate('invoices');
    modules()->uninstall('invoices');
    modules()->install('invoices');
    modules()->activate('invoices');

    expect(modules()->status('invoices'))->toBe('active');
});
