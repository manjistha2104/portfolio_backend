<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    private function transform(Project $project): array
    {
        return [
            'id' => $project->id,
            'name' => $project->name,
            'code' => $project->code,
            'category' => $project->category,
            'description' => $project->description,
            'tech_stack' => $project->tech_stack,
            'image' => $project->image,
            'file' => $project->file,
            'image_url' => $project->image ? asset('storage/' . $project->image) : null,
            'file_url' => $project->file ? asset('storage/' . $project->file) : null,
            'live_url' => $project->live_url,
            'github_url' => $project->github_url,
            
            'created_by' => $project->created_by,
            'created_at' => $project->created_at,
            'updated_at' => $project->updated_at,
        ];
    }

    public function index()
    {
        $projects = Project::latest()->get()->map(function ($project) {
            return $this->transform($project);
        });

        return response()->json($projects);
    }

    public function publicIndex()
    {
        $projects = Project::latest()->get()->map(function ($project) {
            return $this->transform($project);
        });

        return response()->json($projects);
    }

    public function show(Project $project)
    {
        return response()->json($this->transform($project));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:projects,code'],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tech_stack' => ['nullable', 'string'],
            'live_url' => ['nullable', 'string', 'max:255'],
            'github_url' => ['nullable', 'string', 'max:255'],
            
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'file' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('projects/images', 'public');
        }

        if ($request->hasFile('file')) {
            $validated['file'] = $request->file('file')->store('projects/files', 'public');
        }

        $validated['created_by'] = $request->user()->id;
       

        $project = Project::create($validated);

        return response()->json([
            'message' => 'Project created successfully.',
            'project' => $this->transform($project),
        ], 201);
    }

    public function update(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:255', 'unique:projects,code,' . $project->id],
            'category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'tech_stack' => ['nullable', 'string'],
            'live_url' => ['nullable', 'string', 'max:255'],
            'github_url' => ['nullable', 'string', 'max:255'],
           
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'file' => ['nullable', 'file', 'max:5120'],
        ]);

        if ($request->hasFile('image')) {
            if ($project->image && Storage::disk('public')->exists($project->image)) {
                Storage::disk('public')->delete($project->image);
            }

            $validated['image'] = $request->file('image')->store('projects/images', 'public');
        }

        if ($request->hasFile('file')) {
            if ($project->file && Storage::disk('public')->exists($project->file)) {
                Storage::disk('public')->delete($project->file);
            }

            $validated['file'] = $request->file('file')->store('projects/files', 'public');
        }

        

        $project->update($validated);

        return response()->json([
            'message' => 'Project updated successfully.',
            'project' => $this->transform($project->fresh()),
        ]);
    }

    public function destroy(Project $project)
    {
        if ($project->image && Storage::disk('public')->exists($project->image)) {
            Storage::disk('public')->delete($project->image);
        }

        if ($project->file && Storage::disk('public')->exists($project->file)) {
            Storage::disk('public')->delete($project->file);
        }

        $project->delete();

        return response()->json([
            'message' => 'Project deleted successfully.',
        ]);
    }
}