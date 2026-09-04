@extends('layouts.app')

@section('title', 'Quick Test')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-semibold">Quick Test</h1>
            <p class="mt-1 text-sm text-text-muted">
                Send a one-off request. Nothing is saved to a project &mdash; your last request is kept in this browser.
            </p>
        </div>
        <a href="{{ route('projects.index') }}" class="btn btn-secondary">Save requests in a project</a>
    </div>

    @include('partials.request-runner', ['runner' => [
        'method' => 'GET',
        'url' => '',
        'bodyType' => 'json',
        'body' => '',
        'params' => [],
        'headers' => [],
        'persist' => true,
    ]])
@endsection
