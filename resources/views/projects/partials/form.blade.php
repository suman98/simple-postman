@php $project ??= null; @endphp

<div>
    <label for="project-name" class="field-label">Name</label>
    <input id="project-name" type="text" name="name" value="{{ old('name', $project->name ?? '') }}"
           class="field @error('name') border-danger @enderror" required autofocus
           @error('name') aria-invalid="true" aria-describedby="project-name-error" @enderror>
    @error('name')
        <p id="project-name-error" class="mt-1.5 text-sm text-danger">{{ $message }}</p>
    @enderror
</div>

<div>
    <label for="project-description" class="field-label">Description <span class="text-text-faint">(optional)</span></label>
    <textarea id="project-description" name="description" rows="3" class="field">{{ old('description', $project->description ?? '') }}</textarea>
</div>
