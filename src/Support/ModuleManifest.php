<?php

namespace RajuBepary\LaravelModule\Support;

use InvalidArgumentException;

/**
 * Immutable metadata about a module, read from its module.json file.
 */
final readonly class ModuleManifest
{
    public function __construct(
        public string $slug,
        public string $name,
        public string $description,
        public string $version,
        public string $author,
        public string $provider,
        public string $path,
        /**
         * @var array<string, string>
         */
        public array $requires = [],
        /**
         * Protected ("protected": true) modules cannot be deactivated or
         * uninstalled — the CRM equivalent of WordPress must-use plugins.
         */
        public bool $protected = false,
    ) {}

    /**
     * Parse and validate a module.json file.
     */
    public static function fromFile(string $file): self
    {
        $data = json_decode((string) file_get_contents($file), true);

        if (! is_array($data)) {
            throw new InvalidArgumentException("Module manifest [{$file}] is not valid JSON.");
        }

        foreach (['id', 'name', 'description', 'version', 'author', 'provider'] as $key) {
            if (empty($data[$key]) || ! is_string($data[$key])) {
                throw new InvalidArgumentException("Module manifest [{$file}] is missing required key [{$key}].");
            }
        }

        return new self(
            slug: $data['id'],
            name: $data['name'],
            description: $data['description'],
            version: $data['version'],
            author: $data['author'],
            provider: $data['provider'],
            path: dirname($file),
            requires: $data['requires'] ?? [],
            protected: (bool) ($data['protected'] ?? false),
        );
    }

    /**
     * Lifecycle handler names the provider may define, mirroring
     * WordPress register_activation_hook / uninstall.php conventions.
     *
     * @return list<string>
     */
    public static function lifecycleMethods(): array
    {
        return ['activate', 'deactivate', 'uninstall'];
    }
}
