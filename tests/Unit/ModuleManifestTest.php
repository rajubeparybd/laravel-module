<?php

use RajuBepary\LaravelModule\Support\ModuleManifest;

test('manifest parses a valid module.json', function () {
    $file = sys_get_temp_dir().'/'.uniqid('module_').'.json';
    file_put_contents($file, json_encode([
        'id' => 'whatsapp',
        'name' => 'WhatsApp Integration',
        'description' => 'WhatsApp CRM integration',
        'version' => '1.2.0',
        'author' => 'Vendor',
        'provider' => 'App\\Modules\\WhatsApp\\WhatsAppServiceProvider',
        'requires' => ['crm' => '^1.0'],
        'protected' => true,
    ]));

    $manifest = ModuleManifest::fromFile($file);

    expect($manifest->slug)->toBe('whatsapp')
        ->and($manifest->name)->toBe('WhatsApp Integration')
        ->and($manifest->version)->toBe('1.2.0')
        ->and($manifest->provider)->toBe('App\\Modules\\WhatsApp\\WhatsAppServiceProvider')
        ->and($manifest->requires)->toBe(['crm' => '^1.0'])
        ->and($manifest->protected)->toBeTrue();

    unlink($file);
});

test('manifest rejects missing required keys', function () {
    $file = sys_get_temp_dir().'/'.uniqid('module_').'.json';
    file_put_contents($file, json_encode(['id' => 'broken']));

    ModuleManifest::fromFile($file);
})->throws(InvalidArgumentException::class, 'missing required key [name]');

test('manifest defaults to unprotected when the flag is absent', function () {
    $file = sys_get_temp_dir().'/'.uniqid('module_').'.json';
    file_put_contents($file, json_encode([
        'id' => 'plain',
        'name' => 'Plain',
        'description' => 'No protection declared',
        'version' => '1.0.0',
        'author' => 'Vendor',
        'provider' => 'App\\Modules\\Plain\\PlainServiceProvider',
    ]));

    expect(ModuleManifest::fromFile($file)->protected)->toBeFalse();

    unlink($file);
});
