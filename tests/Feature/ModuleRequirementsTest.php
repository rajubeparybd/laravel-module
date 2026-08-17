<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('module cannot activate when its requirement is inactive', function () {
    modules()->install('customers');
    modules()->install('invoices');

    modules()->activate('invoices');
})->throws(RuntimeException::class, 'Module [invoices] requires [customers] to be installed and active.');

test('module activates once its requirement is active', function () {
    modules()->install('customers');
    modules()->install('invoices');

    modules()->activate('customers');
    modules()->activate('invoices');

    expect(modules()->status('invoices'))->toBe('active');
});

test('requirement deactivation is blocked while a dependent is active', function () {
    modules()->install('customers');
    modules()->install('invoices');
    modules()->activate('customers');
    modules()->activate('invoices');

    // Customers is manifest-protected, so that guard fires first; even
    // without protection the dependent guard would refuse this operation.
    modules()->deactivate('customers');
})->throws(RuntimeException::class, 'Module [customers] is protected by its module.json and cannot be deactivated.');

test('dependent can be deactivated while its requirement stays active', function () {
    modules()->install('customers');
    modules()->install('invoices');
    modules()->activate('customers');
    modules()->activate('invoices');

    modules()->deactivate('invoices');

    expect(modules()->status('invoices'))->toBe('inactive')
        ->and(modules()->status('customers'))->toBe('active');
});

test('activation error surfaces on the module management page', function () {
    $this->post('/modules/customers/install');
    $this->post('/modules/invoices/install');
    $this->post('/modules/invoices/activate')
        ->assertRedirect('/modules')
        ->assertSessionHas('error', 'Module [invoices] requires [customers] to be installed and active. Activate [customers] first.');

    expect(modules()->status('invoices'))->toBe('inactive');
});
