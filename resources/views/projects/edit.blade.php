@extends('layouts.app')

@section('title', 'Edit ' . $project->name)

@section('content')
    <a href="{{ route('projects.show', $project) }}" class="text-sm text-text-muted hover:text-text">&larr; {{ $project->name }}</a>
    <h1 class="mt-3 mb-4 text-lg font-semibold">Edit project</h1>

    <form method="POST" action="{{ route('projects.update', $project) }}" class="panel max-w-2xl space-y-5 p-4">
        @csrf
        @method('PUT')
        @include('projects.partials.form')

        <div class="flex flex-wrap items-center gap-2 border-t border-border pt-4">
            <button class="btn btn-primary">Save changes</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
