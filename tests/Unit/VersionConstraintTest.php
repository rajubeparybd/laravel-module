<?php

use RajuBepary\LaravelModule\Support\ModuleManager;

test('caret constraint accepts within same major', function (string $version) {
    expect(ModuleManager::versionSatisfies($version, '^1.0'))->toBeTrue();
})->with(['1.0.0', '1.0', '1.9.9']);

test('caret constraint rejects other majors', function (string $version) {
    expect(ModuleManager::versionSatisfies($version, '^1.0'))->toBeFalse();
})->with(['0.9.0', '2.0.0', '2.1']);

test('comparison operators', function (string $version, string $constraint, bool $expected) {
    expect(ModuleManager::versionSatisfies($version, $constraint))->toBe($expected);
})->with([
    ['1.5.0', '>=1.0', true],
    ['0.9.0', '>=1.0', false],
    ['1.0.0', '1.0', true],
    ['1.0.1', '1.0', false],
    ['0.1.0', '*', true],
]);
