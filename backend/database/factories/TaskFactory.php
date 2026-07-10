<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->boolean(70) ? fake()->paragraph() : null,
            'due_date' => fake()->boolean(70)
                ? fake()->dateTimeBetween('-1 week', '+1 month')->format('Y-m-d')
                : null,
            'status' => fake()->randomElement(TaskStatus::values()),
        ];
    }

    public function status(TaskStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status->value,
        ]);
    }
}
