<?php

namespace Database\Seeders;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Test admin — sees every task.
        $admin = User::factory()->admin()->create([
            'name' => 'Администратор',
            'email' => 'admin@example.com',
        ]);

        // Test regular user — sees only their own tasks.
        $user = User::factory()->create([
            'name' => 'Иван Петров',
            'email' => 'user@example.com',
        ]);

        // A second regular user to prove ownership scoping.
        $other = User::factory()->create([
            'name' => 'Мария Сидорова',
            'email' => 'user2@example.com',
        ]);

        // Test viewer — sees every task like an admin, but can only edit/delete their own.
        $viewer = User::factory()->viewer()->create([
            'name' => 'Наблюдатель',
            'email' => 'viewer@example.com',
        ]);

        // Deterministic tasks so the list has each status represented.
        foreach ([$admin, $user, $other, $viewer] as $owner) {
            Task::factory()->for($owner)->status(TaskStatus::Pending)->count(4)->create();
            Task::factory()->for($owner)->status(TaskStatus::InProgress)->count(3)->create();
            Task::factory()->for($owner)->status(TaskStatus::Completed)->count(3)->create();
        }
    }
}
