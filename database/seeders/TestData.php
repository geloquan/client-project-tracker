<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestData extends Seeder
{
  public function run(): void
  {
    // created_by is a required FK to users, so make sure a user exists.
    $userId = User::query()->value('id') ?? User::factory()->create()->id;

    $projects = [
      ['clientName' => 'Acme Corporation', 'projectName' => 'Corporate Website Redesign', 'description' => "Redesign and modernize the company's corporate website.", 'status' => 'In Progress', 'priority' => 'High', 'startDate' => '2026-06-01', 'dueDate' => '2026-07-15'],
      ['clientName' => 'GreenLeaf Cafe', 'projectName' => 'Online Ordering System', 'description' => 'Develop an online ordering platform for customers.', 'status' => 'Planning', 'priority' => 'Medium', 'startDate' => '2026-06-10', 'dueDate' => '2026-08-01'],
      ['clientName' => 'Bright Realty', 'projectName' => 'Property Listing Portal', 'description' => 'Build a portal for managing property listings.', 'status' => 'On Hold', 'priority' => 'Medium', 'startDate' => '2026-05-15', 'dueDate' => '2026-07-30'],
      ['clientName' => 'Nova Fitness', 'projectName' => 'Mobile App MVP', 'description' => 'Develop the first version of the fitness tracking app.', 'status' => 'In Progress', 'priority' => 'High', 'startDate' => '2026-06-05', 'dueDate' => '2026-08-20'],
      ['clientName' => 'Blue Ocean Travel', 'projectName' => 'Booking Platform Enhancement', 'description' => 'Improve search and booking functionalities.', 'status' => 'Completed', 'priority' => 'Medium', 'startDate' => '2026-04-01', 'dueDate' => '2026-05-30'],
      ['clientName' => 'TechVision Solutions', 'projectName' => 'CRM Dashboard', 'description' => 'Develop an internal CRM dashboard.', 'status' => 'Planning', 'priority' => 'High', 'startDate' => '2026-06-15', 'dueDate' => '2026-08-15'],
      ['clientName' => 'Urban Living', 'projectName' => 'Property Management System', 'description' => 'Create a platform for managing rental properties.', 'status' => 'In Progress', 'priority' => 'Medium', 'startDate' => '2026-05-20', 'dueDate' => '2026-08-10'],
      ['clientName' => 'Elite Events', 'projectName' => 'Event Registration Portal', 'description' => 'Develop a registration and ticketing portal.', 'status' => 'Planning', 'priority' => 'Low', 'startDate' => '2026-06-20', 'dueDate' => '2026-09-01'],
      ['clientName' => 'HealthFirst Clinic', 'projectName' => 'Patient Appointment System', 'description' => 'Build an appointment scheduling application.', 'status' => 'Completed', 'priority' => 'High', 'startDate' => '2026-03-01', 'dueDate' => '2026-05-01'],
      ['clientName' => 'MarketPro', 'projectName' => 'Marketing Campaign Dashboard', 'description' => 'Track and manage digital marketing campaigns.', 'status' => 'In Progress', 'priority' => 'Medium', 'startDate' => '2026-06-01', 'dueDate' => '2026-07-31'],
      ['clientName' => 'Sunrise Education', 'projectName' => 'Learning Management Portal', 'description' => 'Develop a portal for students and instructors.', 'status' => 'Planning', 'priority' => 'High', 'startDate' => '2026-07-01', 'dueDate' => '2026-09-30'],
      ['clientName' => 'FreshFarm', 'projectName' => 'Inventory Management System', 'description' => 'Track inventory across multiple locations.', 'status' => 'On Hold', 'priority' => 'Low', 'startDate' => '2026-05-01', 'dueDate' => '2026-08-01'],
    ];

    foreach ($projects as $item) {
      $start = Carbon::parse($item['startDate']);
      $due = Carbon::parse($item['dueDate']);

      // Every project has been planned; later stages depend on status.
      $timestamps = [
        'planned_at' => $start,
        'in_progress_at' => null,
        'on_hold_at' => null,
        'completed_at' => null,
      ];

      switch ($item['status']) {
        case 'In Progress':
          $timestamps['in_progress_at'] = $start;
          break;

        case 'On Hold':
          $timestamps['in_progress_at'] = $start;
          $timestamps['on_hold_at'] = now();
          break;

        case 'Completed':
          $timestamps['in_progress_at'] = $start;
          $timestamps['completed_at'] = $due;
          break;

        // 'Planning' => only planned_at is set
      }

      Project::updateOrCreate(
        [
          'client_name' => $item['clientName'],
          'project_name' => $item['projectName'],
        ],
        [
          'created_by' => $userId,
          'updated_by' => null,
          'description' => $item['description'],
          'priority' => strtolower($item['priority']),
          ...$timestamps,
        ]
      );
    }
  }
}
