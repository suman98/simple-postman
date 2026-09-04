@extends('layouts.app')

@section('title', 'New Project')

@section('content')
    <h1 class="text-xl font-semibold mb-4">New Project</h1>

    <form method="POST" action="{{ route('projects.store') }}" class="bg-white rounded-lg shadow p-4 max-w-lg space-y-4">
        @csrf
        @include('projects.partials.form')

        <div class="flex gap-2">
            <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium">Create Project</button>
            <a href="{{ route('projects.index') }}" class="px-4 py-2 rounded text-sm text-slate-600 hover:bg-slate-100">Cancel</a>
        </div>
    </form>
@endsection
