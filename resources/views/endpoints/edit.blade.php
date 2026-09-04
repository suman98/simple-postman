@extends('layouts.app')

@section('title', 'Edit ' . $endpoint->name)

@section('content')
    <a href="{{ route('endpoints.show', $endpoint) }}" class="text-sm text-text-muted hover:text-text">&larr; {{ $endpoint->name }}</a>
    <h1 class="mt-3 text-lg font-semibold">Edit endpoint</h1>
    <p class="mt-1 mb-4 text-sm text-text-muted">In {{ $endpoint->project->name }}</p>

    <form method="POST" action="{{ route('endpoints.update', $endpoint) }}" class="panel space-y-5 p-4">
        @csrf
        @method('PUT')
        @include('endpoints.partials.form')

        <div class="flex flex-wrap items-center gap-2 border-t border-border pt-4">
            <button class="btn btn-primary">Save changes</button>
            <a href="{{ route('endpoints.show', $endpoint) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
