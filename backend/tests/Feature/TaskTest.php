<?php

namespace Tests\Feature;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_tasks(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Task::factory()->for($user)->count(3)->create();
        Task::factory()->for($other)->count(2)->create();

        $response = $this->actingAs($user)->getJson('/api/tasks');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'title', 'status', 'can' => ['update', 'delete']]],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_admin_sees_all_tasks(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        Task::factory()->for($user)->count(4)->create();
        Task::factory()->for($admin)->count(1)->create();

        $this->actingAs($admin)->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_viewer_sees_all_tasks_but_cannot_modify_others(): void
    {
        $viewer = User::factory()->viewer()->create();
        $owner = User::factory()->create();
        $task = Task::factory()->for($owner)->create();

        Task::factory()->for($owner)->count(3)->create();
        Task::factory()->for($viewer)->count(1)->create();

        // Sees everyone's tasks, like an admin.
        $this->actingAs($viewer)->getJson('/api/tasks')
            ->assertOk()
            ->assertJsonCount(5, 'data');

        // Can view a single task that isn't theirs.
        $this->actingAs($viewer)->getJson("/api/tasks/{$task->id}")
            ->assertOk();

        // Cannot update someone else's task.
        $this->actingAs($viewer)->patchJson("/api/tasks/{$task->id}", ['status' => 'completed'])
            ->assertStatus(403);

        // Cannot delete someone else's task.
        $this->actingAs($viewer)->deleteJson("/api/tasks/{$task->id}")
            ->assertStatus(403);
        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_viewer_can_still_manage_their_own_task(): void
    {
        $viewer = User::factory()->viewer()->create();
        $ownTask = Task::factory()->for($viewer)->create(['status' => 'pending']);

        $this->actingAs($viewer)->patchJson("/api/tasks/{$ownTask->id}", ['status' => 'completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->actingAs($viewer)->deleteJson("/api/tasks/{$ownTask->id}")
            ->assertNoContent();
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->status(TaskStatus::Pending)->count(2)->create();
        Task::factory()->for($user)->status(TaskStatus::Completed)->count(3)->create();

        $this->actingAs($user)->getJson('/api/tasks?status=completed')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_tasks_can_be_searched(): void
    {
        $user = User::factory()->create();
        Task::factory()->for($user)->create(['title' => 'Buy groceries']);
        Task::factory()->for($user)->create(['title' => 'Write report']);

        $this->actingAs($user)->getJson('/api/tasks?search=groceries')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Buy groceries');
    }

    public function test_user_can_create_a_task(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/tasks', [
            'title' => 'My new task',
            'description' => 'Some details',
            'due_date' => '2026-08-01',
            'status' => 'in_progress',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.title', 'My new task')
            ->assertJsonPath('data.status', 'in_progress');

        $this->assertDatabaseHas('tasks', [
            'title' => 'My new task',
            'user_id' => $user->id,
        ]);
    }

    public function test_creating_a_task_validates_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/tasks', ['title' => 'ab'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('title');
    }

    public function test_owner_can_update_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create(['status' => 'pending']);

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])->assertOk()->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'completed']);
    }

    public function test_user_cannot_update_another_users_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for(User::factory()->create())->create();

        $this->actingAs($user)->patchJson("/api/tasks/{$task->id}", [
            'title' => 'Hacked title',
        ])->assertStatus(403);
    }

    public function test_user_cannot_delete_another_users_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for(User::factory()->create())->create();

        $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('tasks', ['id' => $task->id]);
    }

    public function test_owner_can_delete_their_task(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->for($user)->create();

        $this->actingAs($user)->deleteJson("/api/tasks/{$task->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_admin_can_update_any_task(): void
    {
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->for(User::factory()->create())->create();

        $this->actingAs($admin)->patchJson("/api/tasks/{$task->id}", [
            'status' => 'completed',
        ])->assertOk();
    }

    public function test_missing_task_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/tasks/99999')
            ->assertStatus(404)
            ->assertJson(['message' => 'Resource not found.']);
    }
}
