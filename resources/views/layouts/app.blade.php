<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') &middot; @endif{{ config('app.name', 'API Bench') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <header class="border-b border-border bg-surface">
        <div class="mx-auto flex max-w-[1180px] items-center gap-8 overflow-x-auto px-4 sm:px-6">
            <a href="{{ route('quick-test') }}" class="shrink-0 py-3 text-sm font-semibold">API Bench</a>

            <nav class="flex items-center gap-6">
                @php
                    $navItems = [
                        ['route' => 'quick-test', 'label' => 'Quick Test', 'active' => request()->routeIs('quick-test')],
                        ['route' => 'projects.index', 'label' => 'Projects', 'active' => request()->routeIs('projects.*') || request()->routeIs('endpoints.*')],
                    ];
                @endphp
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}"
                       class="tab whitespace-nowrap py-3 {{ $item['active'] ? 'tab-active' : '' }}"
                       @if ($item['active']) aria-current="page" @endif>
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-[1180px] px-4 py-6 sm:px-6">
        @if (session('status'))
            <p class="mb-5 rounded border border-border bg-surface px-4 py-3 text-sm text-success" role="status">
                {{ session('status') }}
            </p>
        @endif

        @yield('content')
    </main>
</body>
</html>
