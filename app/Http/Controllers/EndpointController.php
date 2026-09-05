<?php

namespace App\Http\Controllers;

use App\Models\Endpoint;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EndpointController extends Controller
{
    public function index(Project $project): RedirectResponse
    {
        return redirect()->route('projects.show', $project);
    }

    public function create(Project $project): View
    {
        return view('endpoints.create', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $this->validated($request);
        $data['project_id'] = $project->id;

        $endpoint = Endpoint::create($data);

        return redirect()->route('endpoints.show', $endpoint)->with('status', 'Endpoint created.');
    }

    public function show(Endpoint $endpoint): View
    {
        $endpoint->load('project');

        return view('endpoints.show', compact('endpoint'));
    }

    public function edit(Endpoint $endpoint): View
    {
        $endpoint->load('project');

        return view('endpoints.edit', compact('endpoint'));
    }

    public function update(Request $request, Endpoint $endpoint): RedirectResponse
    {
        $endpoint->update($this->validated($request));

        return redirect()->route('endpoints.show', $endpoint)->with('status', 'Endpoint updated.');
    }

    public function destroy(Endpoint $endpoint): RedirectResponse
    {
        $project = $endpoint->project;
        $endpoint->delete();

        return redirect()->route('projects.show', $project)->with('status', 'Endpoint deleted.');
    }

    /**
     * Saves the request as it currently stands in the builder on the endpoint
     * page — method, URL, params, headers and body — without leaving the page.
     * Called via fetch from the Save button beside Send, so it answers with
     * JSON rather than redirecting. The name stays untouched; that's the
     * Edit form's job.
     */
    public function updateRequest(Request $request, Endpoint $endpoint): JsonResponse
    {
        $data = $request->validate([
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string',
            'body_type' => 'required|string|in:json,form',
            'body' => 'nullable|string',
            'params' => 'nullable|array',
            'params.*.key' => 'nullable|string',
            'params.*.value' => 'nullable|string',
            'headers' => 'nullable|array',
            'headers.*.key' => 'nullable|string',
            'headers.*.value' => 'nullable|string',
        ]);

        $data['params'] = collect($data['params'] ?? [])
            ->filter(fn ($row) => ! empty($row['key']))
            ->pluck('value', 'key')
            ->toArray();

        $data['headers'] = collect($data['headers'] ?? [])
            ->filter(fn ($row) => ! empty($row['key']))
            ->map(fn ($row) => ['key' => $row['key'], 'value' => $row['value'] ?? ''])
            ->values()
            ->toArray();

        $endpoint->update($data);

        return response()->json(['saved' => true]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'method' => 'required|string|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string',
            'body_type' => 'required|string|in:json,form',
            'body' => 'nullable|string',
            'params' => 'nullable|array',
            'params.*.key' => 'nullable|string',
            'params.*.value' => 'nullable|string',
            'headers' => 'nullable|array',
            'headers.*.key' => 'nullable|string',
            'headers.*.value' => 'nullable|string',
        ]);

        // Store params as a flat key => value map, dropping empty rows.
        $data['params'] = collect($data['params'] ?? [])
            ->filter(fn ($row) => ! empty($row['key']))
            ->pluck('value', 'key')
            ->toArray();

        $data['headers'] = collect($data['headers'] ?? [])
            ->filter(fn ($row) => ! empty($row['key']))
            ->map(fn ($row) => ['key' => $row['key'], 'value' => $row['value'] ?? ''])
            ->values()
            ->toArray();

        return $data;
    }
}
