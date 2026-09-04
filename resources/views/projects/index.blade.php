@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">Projects</h1>
            <p class="mt-1 text-sm text-text-muted">Saved endpoints, grouped by project.</p>
        </div>
        <a href="{{ route('projects.create') }}" class="btn btn-primary">New project</a>
    </div>

    @if ($projects->isEmpty())
        <div class="panel px-6 py-14 text-center">
            <p class="text-sm font-medium">No projects yet</p>
            <p class="mx-auto mt-1.5 max-w-[46ch] text-sm text-text-muted">
                A project keeps a set of endpoints so you can re-run them without retyping the URL.
            </p>
            <a href="{{ route('projects.create') }}" class="btn btn-secondary mt-5">New project</a>
        </div>
    @else
        <ul class="panel divide-y divide-border">
            @foreach ($projects as $project)
                <li>
                    <a href="{{ route('projects.show', $project) }}"
                       class="flex items-center justify-between gap-4 px-4 py-3 hover:bg-canvas">
                        <span class="min-w-0">
                            <span class="block text-sm font-medium">{{ $project->name }}</span>
                            @if ($project->description)
                                <span class="mt-0.5 block truncate text-sm text-text-muted">{{ $project->description }}</span>
                            @endif
                        </span>
                        <span class="shrink-0 text-xs text-text-muted">
                            {{ $project->endpoints_count }} {{ Str::plural('endpoint', $project->endpoints_count) }}
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
@endsection
