@extends('layouts.app')

@section('title', 'Quick Test')

@section('content')
    <h1 class="text-xl font-semibold mb-1">Quick Test</h1>
    <p class="text-sm text-slate-500 mb-4">Fire a request without creating a project. Nothing here is saved.</p>

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
