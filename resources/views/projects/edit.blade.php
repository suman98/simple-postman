@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
    <h1 class="text-xl font-semibold mb-4">Edit Project</h1>

    <form method="POST" action="{{ route('projects.update', $project) }}" class="bg-white rounded-lg shadow p-4 max-w-lg space-y-4">
        @csrf
        @method('PUT')
        @include('projects.partials.form')

        <div class="flex gap-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium">Save Changes</button>
            <a href="{{ route('projects.show', $project) }}" class="px-4 py-2 rounded text-sm text-slate-600 hover:bg-slate-100">Cancel</a>
        </div>
    </form>
@endsection
