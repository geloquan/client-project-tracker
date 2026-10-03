<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Responses\JsonResponse;
use App\Models\Project;
use App\Models\User;
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
    $userId = auth()->id() ?? User::query()->value('id');

    if ($userId === null) {
      return JsonResponse::error('A user is required to create a project.', 422);
    }

    $project = Project::create(array_merge(
      $this->statusAttributes($request->validated()),
      ['created_by' => $userId],
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

    if ($request->has('status')) {
      $attributes = array_merge($attributes, $this->statusAttributes($attributes));
    }

    $attributes['updated_by'] = auth()->id() ?? $project->updated_by;
    $project->update($attributes);

    return JsonResponse::success(new ProjectResource($project->refresh()), 'Project updated successfully.');
  }

  public function destroy(Project $project): LaravelJsonResponse
  {
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

  private function statusAttributes(array $attributes): array
  {
    $status = $attributes['status'] ?? 'planned';
    $timestamp = Carbon::now();

    unset($attributes['status']);

    return array_merge($attributes, [
      'in_progress_at' => $status === 'in_progress' ? ($attributes['in_progress_at'] ?? $timestamp) : null,
      'on_hold_at' => $status === 'on_hold' ? ($attributes['on_hold_at'] ?? $timestamp) : null,
      'completed_at' => $status === 'completed' ? ($attributes['completed_at'] ?? $timestamp) : null,
    ]);
  }
}
