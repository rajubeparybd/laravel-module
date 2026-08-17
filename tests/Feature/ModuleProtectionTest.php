<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('manifest-declared protection flags the module', function () {
    expect(modules()->isProtected('customers'))->toBeTrue()
        ->and(modules()->isProtected('invoices'))->toBeFalse();
});

test('protected module cannot be deactivated', function () {
    modules()->install('customers');
    modules()->activate('customers');

    modules()->deactivate('customers');
})->throws(RuntimeException::class, 'Module [customers] is protected by its module.json and cannot be deactivated.');

test('protected module cannot be uninstalled', function () {
    modules()->install('customers');

    modules()->uninstall('customers');
})->throws(RuntimeException::class, 'Module [customers] is protected by its module.json and cannot be uninstalled.');

test('unprotected modules are unaffected', function () {
    modules()->install('customers');
    modules()->install('invoices');
    modules()->activate('customers');
    modules()->activate('invoices');

    modules()->deactivate('invoices');

    expect(modules()->status('invoices'))->toBe('inactive')
        ->and(modules()->status('customers'))->toBe('active');
});

test('modules page marks protected modules and hides their disable actions', function () {
    modules()->install('customers');
    modules()->install('invoices');
    modules()->activate('customers');
    modules()->activate('invoices');

    $page = $this->get('/modules');

    $page->assertSee('Protected')
        ->assertSee('Core Module');

    $content = $page->getContent();

    // Customers row: no deactivate or uninstall forms. Invoices row: both present.
    $customersRow = strstr($content, 'Customer relationship');
    $invoicesRow = strstr($content, 'Invoice management');

    expect($customersRow)->not->toContain('modules/customers/deactivate')
        ->and($customersRow)->not->toContain('modules/customers/uninstall')
        ->and($invoicesRow)->toContain('modules/invoices/deactivate');
});
