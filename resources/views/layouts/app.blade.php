<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Postman Clone') }} @hasSection('title') - @yield('title') @endif</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800 min-h-screen">
    <nav class="bg-slate-900 text-slate-200">
        <div class="max-w-6xl mx-auto px-4 flex items-center gap-1">
            <span class="font-semibold text-white py-3 pr-6">API Bench</span>
            <a href="{{ route('quick-test') }}"
               class="px-4 py-3 text-sm border-b-2 {{ request()->routeIs('quick-test') ? 'border-blue-400 text-white' : 'border-transparent hover:text-white' }}">
                Quick Test
            </a>
            <a href="{{ route('projects.index') }}"
               class="px-4 py-3 text-sm border-b-2 {{ request()->routeIs('projects.*') || request()->routeIs('endpoints.*') ? 'border-blue-400 text-white' : 'border-transparent hover:text-white' }}">
                Projects
            </a>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="mb-4 rounded-md bg-green-100 text-green-800 px-4 py-2 text-sm">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
