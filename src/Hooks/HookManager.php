<?php

namespace RajuBepary\LaravelModule\Hooks;

/**
 * WordPress-style hook manager: actions notify, filters modify.
 *
 * Actions and filters share one registry, mirroring WordPress behavior.
 */
class HookManager
{
    /**
     * Registered listeners keyed by hook name.
     *
     * @var array<string, array<int, array<string, array{callback: callable, acceptedArgs: int}>>>
     */
    protected array $listeners = [];

    /**
     * Register a listener on a hook.
     */
    public function add(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->listeners[$hook][$priority][$this->callbackId($callback)] = [
            'callback' => $callback,
            'acceptedArgs' => $acceptedArgs,
        ];
    }

    /**
     * Remove a listener from a hook.
     */
    public function remove(string $hook, callable $callback, int $priority = 10): bool
    {
        $id = $this->callbackId($callback);

        if (! isset($this->listeners[$hook][$priority][$id])) {
            return false;
        }

        unset($this->listeners[$hook][$priority][$id]);

        return true;
    }

    /**
     * Check whether a hook has listeners. Returns the matching priority
     * when a callback is given and found, mirroring WordPress semantics.
     */
    public function has(string $hook, ?callable $callback = null): bool|int
    {
        if (! isset($this->listeners[$hook])) {
            return false;
        }

        if ($callback === null) {
            return true;
        }

        foreach ($this->listeners[$hook] as $priority => $listeners) {
            if (isset($listeners[$this->callbackId($callback)])) {
                return $priority;
            }
        }

        return false;
    }

    /**
     * Execute all listeners attached to an action hook.
     */
    public function doAction(string $hook, mixed ...$args): void
    {
        $this->renderDebugBadge('Action', $hook);

        foreach ($this->sortedListeners($hook) as $listener) {
            $this->call($listener, $args);
        }
    }

    /**
     * Pass a value through all listeners attached to a filter hook.
     */
    public function applyFilters(string $hook, mixed $value, mixed ...$args): mixed
    {
        $value = $this->renderDebugComment('Filter', $hook, $value);

        foreach ($this->sortedListeners($hook) as $listener) {
            $value = ($listener['callback'])(...array_slice([$value, ...$args], 0, $listener['acceptedArgs']));
        }

        return $value;
    }

    /**
     * Render a visual badge for actions if debugging is enabled.
     */
    protected function renderDebugBadge(string $type, string $hook): void
    {
        if ($this->isDebugEnabled()) {
            echo "<span style=\"background: #ffeb3b9e; color: #000; font-size: 14px; font-weight: bold; padding: 2px 4px; border: 1px dashed red; text-align: center; z-index: 9999; position: relative; border-radius: 3px; margin: 2px; display: block;\" title=\"{$type}: {$hook}\">⚡ {$hook}</span>";
        }
    }

    /**
     * Prepend an HTML comment for filters if the value is a string and debugging is enabled.
     */
    protected function renderDebugComment(string $type, string $hook, mixed $value): mixed
    {
        if (is_string($value) && $this->isDebugEnabled()) {
            echo "<!-- {$type}: {$hook} -->\n";
        }

        return $value;
    }

    /**
     * Determine if hook debugging is enabled and should be rendered.
     */
    protected function isDebugEnabled(): bool
    {
        if (! function_exists('app') || ! app()->bound('config') || ! app()->bound('request')) {
            return false;
        }

        return config('laravel-module.debug_hooks', false) === true
            && ! app()->runningInConsole()
            && ! request()->expectsJson();
    }

    /**
     * Listeners for a hook sorted by ascending priority.
     *
     * @return array<int, array{callback: callable, acceptedArgs: int}>
     */
    protected function sortedListeners(string $hook): array
    {
        if (! isset($this->listeners[$hook])) {
            return [];
        }

        ksort($this->listeners[$hook]);

        return array_merge(...array_values($this->listeners[$hook]));
    }

    /**
     * Invoke a listener with at most its accepted number of arguments.
     *
     * @param  array{callback: callable, acceptedArgs: int}  $listener
     */
    protected function call(array $listener, array $args): void
    {
        ($listener['callback'])(...array_slice($args, 0, $listener['acceptedArgs']));
    }

    /**
     * Stable identity for a callback within the registry.
     */
    protected function callbackId(callable $callback): string
    {
        if (is_array($callback)) {
            [$target, $method] = $callback;

            return (is_string($target) ? $target : get_class($target).'#'.spl_object_id($target)).'::'.$method;
        }

        if (is_string($callback)) {
            return $callback;
        }

        return 'closure#'.spl_object_id($callback);
    }
}
