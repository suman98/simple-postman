@extends('layouts.app')

@section('title', 'New project')

@section('content')
    <a href="{{ route('projects.index') }}" class="text-sm text-text-muted hover:text-text">&larr; Projects</a>
    <h1 class="mt-3 mb-4 text-lg font-semibold">New project</h1>

    <form method="POST" action="{{ route('projects.store') }}" class="panel max-w-2xl space-y-5 p-4">
        @csrf
        @include('projects.partials.form')

        <div class="flex flex-wrap items-center gap-2 border-t border-border pt-4">
            <button class="btn btn-primary">Create project</button>
            <a href="{{ route('projects.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
