<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $fillable = [
        'created_by',
        'updated_by',
        'client_name',
        'project_name',
        'description',
        'planned_at',
        'in_progress_at',
        'on_hold_at',
        'completed_at',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'planned_at' => 'datetime',
            'in_progress_at' => 'datetime',
            'on_hold_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getStatusAttribute(): string
    {
        return match (true) {
            $this->completed_at !== null => 'completed',
            $this->on_hold_at !== null => 'on_hold',
            $this->in_progress_at !== null => 'in_progress',
            default => 'planned',
        };
    }
}
