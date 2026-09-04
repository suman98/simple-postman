@extends('layouts.app')

@section('title', $endpoint->name)

@section('content')
    <div class="flex items-start justify-between mb-4">
        <div>
            <a href="{{ route('projects.show', $endpoint->project) }}" class="text-sm text-blue-600 hover:underline">
                &larr; {{ $endpoint->project->name }}
            </a>
            <h1 class="text-xl font-semibold mt-1">{{ $endpoint->name }}</h1>
        </div>
        <div class="flex gap-2 shrink-0">
            <a href="{{ route('endpoints.edit', $endpoint) }}" class="px-4 py-2 rounded text-sm text-slate-600 hover:bg-slate-100">Edit</a>
            <form method="POST" action="{{ route('endpoints.destroy', $endpoint) }}" onsubmit="return confirm('Delete this endpoint?');">
                @csrf
                @method('DELETE')
                <button class="px-4 py-2 rounded text-sm text-red-600 hover:bg-red-50">Delete</button>
            </form>
        </div>
    </div>

    @include('partials.request-runner', ['runner' => [
        'method' => $endpoint->method,
        'url' => $endpoint->url,
        'bodyType' => $endpoint->body_type,
        'body' => $endpoint->body,
        'params' => collect($endpoint->params ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->toArray(),
        'headers' => $endpoint->headers ?? [],
    ]])
@endsection
