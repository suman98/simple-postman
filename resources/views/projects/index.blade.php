@extends('layouts.app')

@section('title', 'Projects')

@section('content')
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-xl font-semibold">Projects</h1>
        <a href="{{ route('projects.create') }}" class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium">
            New Project
        </a>
    </div>

    @if ($projects->isEmpty())
        <div class="bg-white rounded-lg shadow p-8 text-center text-slate-500">
            No projects yet. Create one to start grouping endpoints.
        </div>
    @else
        <div class="bg-white rounded-lg shadow divide-y">
            @foreach ($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="flex items-center justify-between px-4 py-3 hover:bg-slate-50">
                    <div>
                        <div class="font-medium">{{ $project->name }}</div>
                        @if ($project->description)
                            <div class="text-sm text-slate-500">{{ $project->description }}</div>
                        @endif
                    </div>
                    <span class="text-sm text-slate-400">{{ $project->endpoints_count }} endpoint{{ $project->endpoints_count === 1 ? '' : 's' }}</span>
                </a>
            @endforeach
        </div>
    @endif
@endsection
