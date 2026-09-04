@php $project ??= null; @endphp

<div>
    <label class="block text-sm font-medium mb-1">Name</label>
    <input type="text" name="name" value="{{ old('name', $project->name ?? '') }}"
           class="w-full border rounded px-3 py-2 text-sm" required autofocus>
    @error('name')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>

<div>
    <label class="block text-sm font-medium mb-1">Description</label>
    <textarea name="description" rows="3" class="w-full border rounded px-3 py-2 text-sm">{{ old('description', $project->description ?? '') }}</textarea>
</div>
