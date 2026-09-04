@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <div class="flex items-start justify-between mb-4">
        <div>
            <h1 class="text-xl font-semibold">{{ $project->name }}</h1>
            @if ($project->description)
                <p class="text-sm text-slate-500 mt-1">{{ $project->description }}</p>
            @endif
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('projects.endpoints.create', $project) }}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium">
                New Endpoint
            </a>
            <a href="{{ route('projects.edit', $project) }}" class="px-4 py-2 rounded text-sm text-slate-600 hover:bg-slate-100">Edit</a>
            <form method="POST" action="{{ route('projects.destroy', $project) }}" onsubmit="return confirm('Delete this project and all its endpoints?');">
                @csrf
                @method('DELETE')
                <button class="px-4 py-2 rounded text-sm text-red-600 hover:bg-red-50">Delete</button>
            </form>
        </div>
    </div>

    @php
        $methodColors = [
            'GET' => 'bg-green-100 text-green-700',
            'POST' => 'bg-blue-100 text-blue-700',
            'PUT' => 'bg-amber-100 text-amber-700',
            'PATCH' => 'bg-amber-100 text-amber-700',
            'DELETE' => 'bg-red-100 text-red-700',
        ];
    @endphp

    @if ($project->endpoints->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center text-slate-500">
            No endpoints yet. Add one to start testing.
        </div>
    @else
        <div class="bg-white rounded-lg shadow divide-y">
            @foreach ($project->endpoints as $endpoint)
                <a href="{{ route('endpoints.show', $endpoint) }}" class="flex items-center gap-3 px-4 py-3 hover:bg-slate-50">
                    <span class="text-xs font-mono font-semibold px-2 py-1 rounded {{ $methodColors[$endpoint->method] ?? 'bg-slate-100 text-slate-700' }}">
                        {{ $endpoint->method }}
                    </span>
                    <div class="min-w-0">
                        <div class="font-medium">{{ $endpoint->name }}</div>
                        <div class="text-sm text-slate-500 truncate">{{ $endpoint->url }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
@endsection
