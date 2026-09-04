@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <a href="{{ route('projects.index') }}" class="text-sm text-text-muted hover:text-text">&larr; Projects</a>

    <div class="mt-3 mb-4 flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h1 class="text-lg font-semibold">{{ $project->name }}</h1>
            @if ($project->description)
                <p class="mt-1 max-w-[70ch] text-sm text-text-muted">{{ $project->description }}</p>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-primary">New endpoint</a>
            <a href="{{ route('projects.edit', $project) }}" class="btn btn-secondary">Edit</a>
            <form method="POST" action="{{ route('projects.destroy', $project) }}"
                  onsubmit="return confirm('Delete {{ addslashes($project->name) }} and its {{ $project->endpoints->count() }} endpoint(s)? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    @if ($project->endpoints->isEmpty())
        <div class="panel px-6 py-14 text-center">
            <p class="text-sm font-medium">No endpoints yet</p>
            <p class="mx-auto mt-1.5 max-w-[46ch] text-sm text-text-muted">
                Save a method, URL and body here and it will be ready to send next time.
            </p>
            <a href="{{ route('projects.endpoints.create', $project) }}" class="btn btn-secondary mt-5">New endpoint</a>
        </div>
    @else
        <ul class="panel divide-y divide-border">
            @foreach ($project->endpoints as $endpoint)
                <li>
                    <a href="{{ route('endpoints.show', $endpoint) }}"
                       class="flex items-center gap-3 px-4 py-3 hover:bg-canvas">
                        <span class="method method-{{ strtolower($endpoint->method) }} w-14 shrink-0">{{ $endpoint->method }}</span>
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">{{ $endpoint->name }}</span>
                            <span class="mt-0.5 block truncate font-mono text-xs text-text-muted">{{ $endpoint->url }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
