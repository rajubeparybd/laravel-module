@extends(config('laravel-module.layout', 'layouts.app'))

@section('title', 'Modules')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold">Modules</h1>
            <p class="mt-1 text-sm text-gray-500">Install, activate, and uninstall modules. Deactivated modules stop booting
                entirely.</p>
        </div>
        @do_action('modules.index.toolbar')
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow">
        <table class="w-full text-sm">
            <thead class="border-b bg-gray-50 text-left text-gray-600">
                <tr>
                    <th class="px-4 py-3">Module</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($modules->discover() as $slug => $manifest)
                    @php($status = $modules->status($slug))
                    @php($protected = $modules->isProtected($slug))
                    <tr class="border-b last:border-0 {{ $status === 'active' ? 'bg-green-50/50' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-4">
                            <p class="font-semibold {{ $status === 'active' ? 'text-green-800' : 'text-gray-900' }}">
                                {{ $manifest->name }}
                                @if ($protected)
                                    <span class="ml-1 text-xs font-normal text-gray-400"
                                        title="Protected by its module.json">&#128274; Protected</span>
                                @endif
                            </p>
                            <p class="mt-0.5 text-gray-600">{{ $manifest->description }}</p>
                            <p class="mt-1 text-xs text-gray-400">
                                Version {{ $manifest->version }} | by {{ $manifest->author }}
                            </p>
                        </td>
                        <td class="px-4 py-4">
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium {{ match ($status) {
                                    'active' => 'bg-green-100 text-green-800',
                                    'inactive' => 'bg-gray-100 text-gray-600',
                                    default => 'bg-red-50 text-red-700',
                                } }}">
                                {{ ucfirst($status) }}
                            </span>
                        </td>
                        <style>
                            button:hover {
                                cursor: pointer !important;
                            }
                        </style>
                        <td class="px-4 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($status === 'active' && $protected)
                                    <span class="text-xs text-gray-400">Core Module</span>
                                @elseif ($status === 'active')
                                    <form action="{{ route('modules.deactivate', $slug) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100">
                                            Deactivate
                                        </button>
                                    </form>
                                @elseif ($status === 'inactive')
                                    <form action="{{ route('modules.activate', $slug) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                            Activate
                                        </button>
                                    </form>
                                    @unless ($protected)
                                        <form action="{{ route('modules.uninstall', $slug) }}" method="POST"
                                            onsubmit="return confirm('Uninstall {{ $manifest->name }}? The module code stays on disk.')">
                                            @csrf
                                            <button type="submit"
                                                class="rounded border border-red-300 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                                                Delete
                                            </button>
                                        </form>
                                    @endunless
                                @else
                                    <form action="{{ route('modules.install', $slug) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="rounded bg-blue-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-blue-700">
                                            Install
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-gray-500">No modules found in
                            {{ config('laravel-module.path', app_path('Modules')) }}.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @do_action('modules.index.after')
@endsection
