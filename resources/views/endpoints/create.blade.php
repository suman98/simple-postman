@extends('layouts.app')

@section('title', 'New endpoint')

@php
    $endpoint = null;
    $formConfig = [
        'method' => old('method', 'GET'),
        'url' => old('url', ''),
        'bodyType' => old('body_type', 'json'),
        'body' => old('body', ''),
        'params' => old('params', []),
        'formRows' => [],
        'headers' => old('headers', []),
    ];
@endphp

@section('content')
    <a href="{{ route('projects.show', $project) }}" class="text-sm text-text-muted hover:text-text">&larr; {{ $project->name }}</a>
    <h1 class="mt-3 mb-4 text-lg font-semibold">New endpoint</h1>

    <form method="POST" action="{{ route('projects.endpoints.store', $project) }}" class="panel space-y-5 p-4"
          x-data="endpointForm({{ Illuminate\Support\Js::from($formConfig) }})"
          @submit.capture="ensureHeaderRowsSynced()">
        @csrf
        @include('endpoints.partials.form')

        <div class="flex flex-wrap items-center gap-2 border-t border-border pt-4">
            <button class="btn btn-primary">Save endpoint</button>
            <a href="{{ route('projects.show', $project) }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
@endsection
