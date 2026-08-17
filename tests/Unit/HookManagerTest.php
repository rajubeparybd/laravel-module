<?php

use RajuBepary\LaravelModule\Hooks\HookManager;

beforeEach(function () {
    $this->hooks = new HookManager;
});

test('doAction invokes callbacks with args', function () {
    $seen = [];
    $this->hooks->add('customer.created', function ($customer) use (&$seen) {
        $seen[] = $customer;
    });

    $this->hooks->doAction('customer.created', 'Acme');

    expect($seen)->toBe(['Acme']);
});

test('actions run in priority order', function () {
    $order = [];
    $this->hooks->add('t.order', function () use (&$order) {
        $order[] = 'low';
    }, 20);
    $this->hooks->add('t.order', function () use (&$order) {
        $order[] = 'high';
    }, 5);

    $this->hooks->doAction('t.order');

    expect($order)->toBe(['high', 'low']);
});

test('applyFilters chains values through callbacks', function () {
    $this->hooks->add('invoice.total', fn ($total) => $total + 10, 10);
    $this->hooks->add('invoice.total', fn ($total) => $total * 2, 20);

    expect($this->hooks->applyFilters('invoice.total', 100))->toBe(220);
});

test('filter receives extra args', function () {
    $this->hooks->add('t.args', fn ($value, $multiplier) => $value * $multiplier, 10, 2);

    expect($this->hooks->applyFilters('t.args', 5, 3))->toBe(15);
});

test('accepted args limit passed arguments', function () {
    $received = null;
    $this->hooks->add('t.slice', function (...$args) use (&$received) {
        $received = $args;
    }, 10, 2);

    $this->hooks->doAction('t.slice', 'a', 'b', 'c');

    expect($received)->toBe(['a', 'b']);
});

test('remove detaches callback', function () {
    $callback = fn ($value) => $value + 1;

    $this->hooks->add('t.remove', $callback);
    expect($this->hooks->has('t.remove', $callback))->toBe(10);

    $removed = $this->hooks->remove('t.remove', $callback);

    expect($removed)->toBeTrue()
        ->and($this->hooks->has('t.remove', $callback))->toBeFalse()
        ->and($this->hooks->applyFilters('t.remove', 5))->toBe(5);
});

test('has returns priority of registered callback', function () {
    $callback = fn ($value) => $value;

    $this->hooks->add('t.priority', $callback, 42);

    expect($this->hooks->has('t.priority', $callback))->toBe(42)
        ->and($this->hooks->has('t.missing'))->toBeFalse();
});

test('actions and filters share one registry', function () {
    $callback = fn ($value) => $value;

    $this->hooks->add('t.shared', $callback, 7);

    expect($this->hooks->has('t.shared', $callback))->toBe(7);
});
