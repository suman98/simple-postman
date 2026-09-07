@extends('layouts.app')

@section('title', 'Edit ' . $endpoint->name)

@php
    $storedMethod = old('method', $endpoint->method);
    $storedRows = collect($endpoint->params ?? [])->map(fn ($v, $k) => ['key' => $k, 'value' => $v])->values()->toArray();
    $formConfig = [
        'method' => $storedMethod,
        'url' => old('url', $endpoint->url),
        'bodyType' => old('body_type', $endpoint->body_type),
        'body' => old('body', $endpoint->body ?? ''),
        'params' => old('params', $storedMethod === 'GET' ? $storedRows : []),
        'formRows' => old('params', $storedMethod !== 'GET' ? $storedRows : []),
        'headers' => old('headers', $endpoint->headers ?? []),
    ];
@endphp

@section('content')
    <a href="{{ route('endpoints.show', $endpoint) }}" class="text-sm text-text-muted hover:text-text">&larr; {{ $endpoint->name }}</a>
    <h1 class="mt-3 text-lg font-semibold">Edit endpoint</h1>
    <p class="mt-1 mb-4 text-sm text-text-muted">In {{ $endpoint->project->name }}</p>

    <form method="POST" action="{{ route('endpoints.update', $endpoint) }}" class="panel space-y-5 p-4"
          x-data="endpointForm({{ Illuminate\Support\Js::from($formConfig) }})"
          @submit.capture="ensureHeaderRowsSynced()">
        @csrf
        @method('PUT')
        @include('endpoints.partials.form')

        <div class="flex flex-wrap items-center gap-2 border-t border-border pt-4">
            <button class="btn btn-primary">Save changes</button>
            <a href="{{ route('endpoints.show', $endpoint) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
