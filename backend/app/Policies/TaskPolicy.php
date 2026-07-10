<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    /**
     * Any authenticated user can list tasks (results are scoped in the query).
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user can create their own task.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Admins and viewers may view any task; regular users only their own.
     */
    public function view(User $user, Task $task): bool
    {
        return $user->canViewAllTasks() || $task->user_id === $user->id;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->user_id === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $task->user_id === $user->id;
    }
}
