<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectController extends Controller
{
    public function index(): View
    {
        $projects = Project::withCount('endpoints')->latest()->get();

        return view('projects.index', compact('projects'));
    }

    public function create(): View
    {
        return view('projects.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project = Project::create($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project created.');
    }

    public function show(Project $project): View
    {
        $project->load('endpoints');

        return view('projects.show', compact('project'));
    }

    public function edit(Project $project): View
    {
        return view('projects.edit', compact('project'));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        $project->update($data);

        return redirect()->route('projects.show', $project)->with('status', 'Project updated.');
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('projects.index')->with('status', 'Project deleted.');
    }

    /**
     * Saves this project's environment variables, used to resolve {{name}}
     * placeholders in its endpoints' URL/params/headers/body at send time.
     * Called via fetch from the request builder, so it responds with JSON
     * rather than redirecting.
     */
    public function updateEnvironment(Request $request, Project $project): JsonResponse
    {
        $data = $request->validate([
            'variables' => 'array',
            'variables.*.key' => 'nullable|string|max:255',
            'variables.*.value' => 'nullable|string',
            'variables.*.enabled' => 'nullable|boolean',
        ]);

        $variables = collect($data['variables'] ?? [])
            ->filter(fn ($row) => ! empty($row['key']))
            ->map(fn ($row) => [
                'key' => $row['key'],
                'value' => $row['value'] ?? '',
                'enabled' => $row['enabled'] ?? true,
            ])
            ->values()
            ->all();

        $project->update(['variables' => $variables]);

        return response()->json(['variables' => $variables]);
    }
}
