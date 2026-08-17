<?php

use RajuBepary\LaravelModule\Hooks\HookManager;

if (! function_exists('add_action')) {
    /**
     * Attach a callback to an action hook.
     */
    function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        app(HookManager::class)->add($hook, $callback, $priority, $acceptedArgs);
    }
}

if (! function_exists('do_action')) {
    /**
     * Execute all callbacks attached to an action hook.
     */
    function do_action(string $hook, mixed ...$args): void
    {
        app(HookManager::class)->doAction($hook, ...$args);
    }
}

if (! function_exists('add_filter')) {
    /**
     * Attach a callback to a filter hook.
     */
    function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        app(HookManager::class)->add($hook, $callback, $priority, $acceptedArgs);
    }
}

if (! function_exists('apply_filters')) {
    /**
     * Pass a value through all callbacks attached to a filter hook.
     */
    function apply_filters(string $hook, mixed $value, mixed ...$args): mixed
    {
        return app(HookManager::class)->applyFilters($hook, $value, ...$args);
    }
}

if (! function_exists('remove_action')) {
    /**
     * Remove a callback from an action hook.
     */
    function remove_action(string $hook, callable $callback, int $priority = 10): bool
    {
        return app(HookManager::class)->remove($hook, $callback, $priority);
    }
}

if (! function_exists('remove_filter')) {
    /**
     * Remove a callback from a filter hook.
     */
    function remove_filter(string $hook, callable $callback, int $priority = 10): bool
    {
        return app(HookManager::class)->remove($hook, $callback, $priority);
    }
}

if (! function_exists('has_action')) {
    /**
     * Whether the action hook has callbacks. Returns priority when callback given.
     */
    function has_action(string $hook, ?callable $callback = null): bool|int
    {
        return app(HookManager::class)->has($hook, $callback);
    }
}

if (! function_exists('has_filter')) {
    /**
     * Whether the filter hook has callbacks. Returns priority when callback given.
     */
    function has_filter(string $hook, ?callable $callback = null): bool|int
    {
        return app(HookManager::class)->has($hook, $callback);
    }
}
