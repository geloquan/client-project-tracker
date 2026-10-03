<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_name' => $this->client_name,
            'project_name' => $this->project_name,
            'description' => $this->description,
            'planned_at' => $this->planned_at?->toISOString(),
            'status' => $this->status,
            'in_progress_at' => $this->in_progress_at?->toISOString(),
            'on_hold_at' => $this->on_hold_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'priority' => $this->priority,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
