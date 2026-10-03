<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\TransitionProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Responses\JsonResponse;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse as LaravelJsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ProjectController extends Controller
{
    public function index(Request $request): LaravelJsonResponse
    {
        $request->validate([
            'search' => ['sometimes', 'string'],
            'status' => ['sometimes', 'in:planned,in_progress,on_hold,completed'],
            'priority' => ['sometimes', 'in:low,medium,high'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ], [
            'status.in' => 'Status must be valid.',
            'priority.in' => 'Priority must be valid.',
        ]);

        $projects = Project::query()
            ->when($request->filled('search'), function (Builder $query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('client_name', 'like', "%{$search}%")
                        ->orWhere('project_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function (Builder $query) use ($request): void {
                $this->applyStatusFilter($query, $request->string('status')->toString());
            })
            ->when($request->filled('priority'), function (Builder $query) use ($request): void {
                $query->where('priority', $request->string('priority')->toString());
            })
            ->latest()
            ->paginate(min($request->integer('per_page', 15), 100))
            ->withQueryString();

        return ProjectResource::collection($projects)
            ->additional(['message' => 'Projects retrieved successfully.'])
            ->response();
    }

    public function store(StoreProjectRequest $request): LaravelJsonResponse
    {
        $attributes = $request->validated();
        $attributes['planned_at'] = Carbon::parse($attributes['planned_at'])->endOfDay();

        $project = Project::create(array_merge(
            $attributes,
            ['in_progress_at' => Carbon::now()],
            ['created_by' => auth()->id()],
        ));

        return JsonResponse::success(new ProjectResource($project), 'Project created successfully.', 201);
    }

    public function show(Project $project): LaravelJsonResponse
    {
        return JsonResponse::success(new ProjectResource($project), 'Project retrieved successfully.');
    }

    public function update(UpdateProjectRequest $request, Project $project): LaravelJsonResponse
    {
        $attributes = $request->validated();
        if (isset($attributes['planned_at'])) {
            $attributes['planned_at'] = Carbon::parse($attributes['planned_at'])->endOfDay();
        }

        $attributes['updated_by'] = auth()->id();
        $project->update($attributes);

        return JsonResponse::success(new ProjectResource($project->refresh()), 'Project updated successfully.');
    }

    public function transition(TransitionProjectRequest $request, Project $project): LaravelJsonResponse
    {
        $action = $request->validated('action');

        if ($project->status === 'completed' || ($project->status === 'on_hold' && $action === 'on_hold')) {
            return JsonResponse::error('This project cannot move to that status.', 422);
        }

        if ($action === 'on_hold' && $project->status !== 'in_progress') {
            return JsonResponse::error('Only in-progress projects can be put on hold.', 422);
        }

        if ($action === 'complete' && ! in_array($project->status, ['in_progress', 'on_hold'], true)) {
            return JsonResponse::error('Only active projects can be completed.', 422);
        }

        $project->update([
            'on_hold_at' => $action === 'on_hold' ? Carbon::now() : $project->on_hold_at,
            'completed_at' => $action === 'complete' ? Carbon::now() : null,
            'updated_by' => auth()->id(),
        ]);

        return JsonResponse::success(new ProjectResource($project->refresh()), 'Project status updated successfully.');
    }

    public function destroy(Request $request, Project $project): LaravelJsonResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Password is required to delete a project.',
            'password.current_password' => 'The password is incorrect.',
        ]);

        $project->delete();

        return JsonResponse::success(null, 'Project deleted successfully.');
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        match ($status) {
            'completed' => $query->whereNotNull('completed_at'),
            'on_hold' => $query->whereNull('completed_at')
                ->whereNotNull('on_hold_at'),
            'in_progress' => $query->whereNull('completed_at')
                ->whereNull('on_hold_at')
                ->whereNotNull('in_progress_at'),
            'planned' => $query->whereNull('in_progress_at')
                ->whereNull('on_hold_at')
                ->whereNull('completed_at'),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
