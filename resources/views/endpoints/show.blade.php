@extends('layouts.app')

@section('title', $endpoint->name)

@section('content')
    <a href="{{ route('projects.show', $endpoint->project) }}" class="text-sm text-text-muted hover:text-text">
        &larr; {{ $endpoint->project->name }}
    </a>

    <div class="mt-3 mb-4 flex flex-wrap items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="method method-{{ strtolower($endpoint->method) }}">{{ $endpoint->method }}</span>
            <h1 class="text-lg font-semibold">{{ $endpoint->name }}</h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('endpoints.edit', $endpoint) }}" class="btn btn-secondary">Edit</a>
            <form method="POST" action="{{ route('endpoints.destroy', $endpoint) }}"
                  onsubmit="return confirm('Delete {{ addslashes($endpoint->name) }}? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>

    <p class="mb-4 text-sm text-text-muted">
        Changes here only affect this run. Use Edit to update the saved endpoint.
    </p>

    @include('partials.request-runner', ['runner' => [
        'method' => $endpoint->method,
        'url' => $endpoint->url,
        'bodyType' => $endpoint->body_type,
        'body' => $endpoint->body,
        'params' => collect($endpoint->params ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->toArray(),
        'headers' => $endpoint->headers ?? [],
        'persist' => false,
    ]])
@endsection
