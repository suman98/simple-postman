@extends('layouts.app')

@section('title', 'New Endpoint')

@section('content')
    <h1 class="text-xl font-semibold mb-1">New Endpoint</h1>
    <p class="text-sm text-slate-500 mb-4">In {{ $project->name }}</p>

    <form method="POST" action="{{ route('projects.endpoints.store', $project) }}" class="bg-white rounded-lg shadow p-4 max-w-3xl space-y-4">
        @csrf
        @include('endpoints.partials.form')

        <div class="flex gap-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium">Create Endpoint</button>
            <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 rounded text-sm text-slate-600 hover:bg-slate-100">Cancel</a>
        </div>
    </form>
@endsection
