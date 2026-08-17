<?php

namespace RajuBepary\LaravelModule\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller as BaseController;
use RajuBepary\LaravelModule\Support\ModuleManager;
use RuntimeException;

class ModuleController extends BaseController
{
    /**
     * Module management screen, like the WordPress Plugins page.
     */
    public function index(ModuleManager $modules): View
    {
        $view = apply_filters('laravel_module_index_view', 'laravel-module::index');

        return view($view, ['modules' => $modules]);
    }

    public function install(ModuleManager $modules, string $slug): RedirectResponse
    {
        return $this->transition($modules, $slug, 'install');
    }

    public function activate(ModuleManager $modules, string $slug): RedirectResponse
    {
        return $this->transition($modules, $slug, 'activate');
    }

    public function deactivate(ModuleManager $modules, string $slug): RedirectResponse
    {
        return $this->transition($modules, $slug, 'deactivate');
    }

    public function uninstall(ModuleManager $modules, string $slug): RedirectResponse
    {
        return $this->transition($modules, $slug, 'uninstall');
    }

    /**
     * Run a lifecycle transition and report back to the module list.
     */
    protected function transition(ModuleManager $modules, string $slug, string $action): RedirectResponse
    {
        $pastTense = ['install' => 'installed', 'activate' => 'activated', 'deactivate' => 'deactivated', 'uninstall' => 'uninstalled'][$action];

        try {
            $modules->{$action}($slug);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('modules.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('modules.index')
            ->with('success', "Module [{$slug}] {$pastTense}.");
    }
}
