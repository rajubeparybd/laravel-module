<?php

namespace RajuBepary\LaravelModule\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use RajuBepary\LaravelModule\Models\Module;
use RuntimeException;
use Throwable;

/**
 * WordPress-style module lifecycle manager.
 *
 * States: code on disk -> installed (DB row) -> active (is_active) -> ... -> uninstalled.
 * Only active modules have their service provider registered, so a deactivated
 * module's routes, views, hooks, and migrations vanish exactly like a
 * deactivated WordPress plugin.
 */
class ModuleManager
{
    /** @var array<string, ModuleManifest>|null */
    protected ?array $discovered = null;

    /** @var array<int, string>|null */
    protected ?array $activeSlugs = null;

    public function __construct(protected Application $app) {}

    /**
     * All modules found on disk, keyed by slug.
     *
     * @return array<string, ModuleManifest>
     */
    public function discover(): array
    {
        if ($this->discovered === null) {
            $this->discovered = [];

            $path = config('laravel-module.path', $this->app->path('Modules'));
            foreach (glob($path.'/*/module.json') ?: [] as $file) {
                $manifest = ModuleManifest::fromFile($file);
                $this->discovered[$manifest->slug] = $manifest;
            }
        }

        return $this->discovered;
    }

    /**
     * Manifest for a module, or throw when the slug is unknown.
     */
    public function manifest(string $slug): ModuleManifest
    {
        $path = config('laravel-module.path', $this->app->path('Modules'));

        return $this->discover()[$slug]
            ?? throw new RuntimeException("Module [{$slug}] does not exist in {$path}.");
    }

    /**
     * Slugs of active modules.
     *
     * Fails open before the modules table exists (fresh install, migrations
     * not yet run) and bootstraps every discovered module as installed +
     * active when the table exists but is empty — the WordPress default
     * plugins behaviour on first boot.
     *
     * @return array<int, string>
     */
    public function activeSlugs(): array
    {
        if ($this->activeSlugs !== null) {
            return $this->activeSlugs;
        }

        try {
            $tableName = config('laravel-module.table_name', 'modules');
            if (! Schema::hasTable($tableName)) {
                return $this->activeSlugs = array_keys($this->discover());
            }

            $slugs = Module::query()->where('is_active', true)->pluck('slug')->all();

            if ($slugs === []) {
                $notYetActivated = [];

                foreach ($this->discover() as $slug => $manifest) {
                    $this->install($slug);
                    $notYetActivated[] = $slug;
                }

                // Second pass so activation order does not matter when one
                // module requires another; anything still failing stays
                // installed but inactive, visible on the module page.
                foreach ($notYetActivated as $slug) {
                    try {
                        $this->activate($slug);
                    } catch (RuntimeException) {
                        //
                    }
                }

                return $this->activeSlugs = Module::query()->where('is_active', true)->pluck('slug')->all();
            }

            return $this->activeSlugs = $slugs;
        } catch (Throwable) {
            return $this->activeSlugs = array_keys($this->discover());
        }
    }

    public function isActive(string $slug): bool
    {
        return in_array($slug, $this->activeSlugs(), true);
    }

    /**
     * Whether the module declared itself protected in module.json — the
     * CRM equivalent of WordPress must-use plugins.
     */
    public function isProtected(string $slug): bool
    {
        return $this->manifest($slug)->protected;
    }

    /**
     * Lifecycle state of a module: not-installed, inactive, or active.
     */
    public function status(string $slug): string
    {
        $this->manifest($slug);

        $module = Module::query()->where('slug', $slug)->first();

        return match (true) {
            $module === null => 'not-installed',
            $module->is_active => 'active',
            default => 'inactive',
        };
    }

    /**
     * Register the module in the database without activating it.
     */
    public function install(string $slug): void
    {
        $manifest = $this->manifest($slug);

        if (Module::query()->where('slug', $slug)->exists()) {
            throw new RuntimeException("Module [{$slug}] is already installed.");
        }

        Module::query()->create([
            'slug' => $slug,
            'name' => $manifest->name,
            'version' => $manifest->version,
            'is_active' => false,
        ]);

        do_action('module.installed', $manifest);
    }

    /**
     * Activate an installed module. Boots it on the next request; fires the
     * provider's activate() hook when defined.
     */
    public function activate(string $slug): void
    {
        $module = $this->module($slug);

        if ($module->is_active) {
            throw new RuntimeException("Module [{$slug}] is already active.");
        }

        $this->assertRequirementsMet($slug);

        $module->update(['is_active' => true]);
        $this->activeSlugs = null;

        $this->runLifecycleMethod($slug, 'activate');

        do_action('module.activated', $this->manifest($slug));
    }

    /**
     * Deactivate an active module. Its provider stops booting immediately
     * after the current request, like a deactivated WordPress plugin.
     */
    public function deactivate(string $slug): void
    {
        $this->assertNotProtected($slug, 'deactivated');

        $module = $this->module($slug);

        if (! $module->is_active) {
            throw new RuntimeException("Module [{$slug}] is not active.");
        }

        $this->assertNoActiveDependents($slug);

        $module->update(['is_active' => false]);
        $this->activeSlugs = null;

        $this->runLifecycleMethod($slug, 'deactivate');

        do_action('module.deactivated', $this->manifest($slug));
    }

    /**
     * Remove an inactive module from the database. Runs the provider's
     * uninstall() hook when defined so the module can drop its own tables.
     */
    public function uninstall(string $slug): void
    {
        $this->assertNotProtected($slug, 'uninstalled');

        $module = $this->module($slug);

        if ($module->is_active) {
            throw new RuntimeException("Module [{$slug}] must be deactivated before uninstalling.");
        }

        $this->runLifecycleMethod($slug, 'uninstall');

        $module->delete();
        $this->activeSlugs = null;

        do_action('module.uninstalled', $this->manifest($slug));
    }

    /**
     * Ensure every module the given module requires is installed, active,
     * and at an acceptable version. `requires: {"crm": ...}` refers to the
     * core itself and is not enforced here.
     */
    protected function assertRequirementsMet(string $slug): void
    {
        foreach ($this->manifest($slug)->requires as $required => $constraint) {
            if ($required === 'crm') {
                continue;
            }

            $this->manifest($required);

            $installedVersion = $this->manifest($required)->version;

            if ($this->status($required) !== 'active') {
                throw new RuntimeException(
                    "Module [{$slug}] requires [{$required}] to be installed and active. Activate [{$required}] first.",
                );
            }

            if (! static::versionSatisfies($installedVersion, $constraint)) {
                throw new RuntimeException(
                    "Module [{$slug}] requires [{$required}] {$constraint}, but version {$installedVersion} is installed.",
                );
            }
        }
    }

    /**
     * Refuse lifecycle operations on protected modules.
     */
    protected function assertNotProtected(string $slug, string $operation): void
    {
        if ($this->isProtected($slug)) {
            throw new RuntimeException(
                "Module [{$slug}] is protected by its module.json and cannot be {$operation}.",
            );
        }
    }

    /**
     * Prevent deactivating a module that another active module depends on.
     */
    protected function assertNoActiveDependents(string $slug): void
    {
        foreach ($this->discover() as $otherSlug => $manifest) {
            if ($otherSlug === $slug || ! array_key_exists($slug, $manifest->requires)) {
                continue;
            }

            if ($this->isActive($otherSlug)) {
                throw new RuntimeException(
                    "Module [{$otherSlug}] requires [{$slug}]. Deactivate [{$otherSlug}] first.",
                );
            }
        }
    }

    /**
     * Check a version against a constraint. Supports "^1.0" (semver caret),
     * ">=1.0", exact "1.0", and "*".
     */
    public static function versionSatisfies(string $version, string $constraint): bool
    {
        if ($constraint === '*' || $constraint === '') {
            return true;
        }

        $normalize = fn (string $value): string => implode('.', array_pad(
            array_map(intval(...), explode('.', $value)),
            3,
            0,
        ));

        $version = $normalize($version);

        if (str_starts_with($constraint, '^')) {
            $minimum = $normalize(substr($constraint, 1));
            $major = (int) (explode('.', $minimum)[0]);

            return version_compare($version, $minimum, '>=')
                && version_compare($version, ($major + 1).'.0.0', '<');
        }

        if (str_starts_with($constraint, '>=')) {
            return version_compare($version, $normalize(substr($constraint, 2)), '>=');
        }

        return version_compare($version, $normalize($constraint), '=');
    }

    /**
     * Installed module record for a slug, or throw when not installed.
     */
    protected function module(string $slug): Module
    {
        $this->manifest($slug);

        return Module::query()->where('slug', $slug)->first()
            ?? throw new RuntimeException("Module [{$slug}] is not installed. Run crm:module:install first.");
    }

    /**
     * Instantiate the provider without booting it and invoke a lifecycle
     * method when the module author defined one.
     */
    protected function runLifecycleMethod(string $slug, string $method): void
    {
        $provider = $this->manifest($slug)->provider;

        if (! class_exists($provider) || ! method_exists($provider, $method)) {
            return;
        }

        /** @var ServiceProvider $instance */
        $instance = new $provider($this->app);

        $instance->{$method}();
    }
}
