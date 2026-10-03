<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::create('projects', function (Blueprint $table) {
      $table->id();
      $table->foreignId('created_by')->constrained('users');
      $table->foreignId('updated_by')->nullable()->constrained('users');
      $table->string('client_name');
      $table->string('project_name');
      $table->string('description')->nullable();
      $table->dateTime('planned_at');
      $table->dateTime('in_progress_at')->nullable();
      $table->dateTime('on_hold_at')->nullable();
      $table->dateTime('completed_at')->nullable();
      $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('projects');
  }
};
