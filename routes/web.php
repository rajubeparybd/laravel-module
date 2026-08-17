<?php

use Illuminate\Support\Facades\Route;
use RajuBepary\LaravelModule\Http\Controllers\ModuleController;

$prefix = config('laravel-module.route_prefix', 'modules');

Route::middleware('web')->prefix($prefix)->group(function (): void {
    Route::get('/', [ModuleController::class, 'index'])->name('modules.index');
    Route::post('{slug}/install', [ModuleController::class, 'install'])->name('modules.install');
    Route::post('{slug}/activate', [ModuleController::class, 'activate'])->name('modules.activate');
    Route::post('{slug}/deactivate', [ModuleController::class, 'deactivate'])->name('modules.deactivate');
    Route::post('{slug}/uninstall', [ModuleController::class, 'uninstall'])->name('modules.uninstall');
});
