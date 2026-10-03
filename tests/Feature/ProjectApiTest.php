<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_projects_can_be_created_and_returned_as_a_resource(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/projects', [
            'client_name' => 'Acme',
            'project_name' => 'Website',
            'planned_at' => '2026-12-01',
            'in_progress_at' => '2026-11-01',
            'status' => 'in_progress',
            'priority' => 'high',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.client_name', 'Acme')
            ->assertJsonPath('data.status', 'in_progress');
    }

    public function test_project_listing_supports_search_and_filters(): void
    {
        $user = User::factory()->create();
        Project::create([
            'created_by' => $user->id,
            'client_name' => 'Acme',
            'project_name' => 'Website',
            'planned_at' => '2026-12-01',
            'priority' => 'high',
            'completed_at' => now(),
        ]);
        Project::create([
            'created_by' => $user->id,
            'client_name' => 'Other',
            'project_name' => 'Mobile App',
            'planned_at' => '2026-12-01',
            'priority' => 'low',
        ]);

        $this->getJson('/api/projects?search=Acme&status=completed&priority=high')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.project_name', 'Website');
    }

    public function test_invalid_project_data_returns_meaningful_errors(): void
    {
        $response = $this->postJson('/api/projects', [
            'planned_at' => '2026-01-01',
            'in_progress_at' => '2026-02-01',
            'status' => 'invalid',
            'priority' => 'invalid',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'client_name',
                'project_name',
                'status',
                'priority',
                'planned_at',
            ]);
    }
}
