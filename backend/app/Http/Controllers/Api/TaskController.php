<?php

namespace App\Http\Controllers\Api;

use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /** Columns that may be used for sorting. */
    private const SORTABLE = ['due_date', 'status', 'title', 'created_at'];

    /**
     * List tasks with search, status filter, sorting and pagination.
     * Regular users see only their own tasks; admins and viewers see everything.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Task::class);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'sort' => ['nullable', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $user = $request->user();

        $query = Task::query()
            ->when(! $user->canViewAllTasks(), fn ($q) => $q->where('user_id', $user->id))
            ->when($user->canViewAllTasks(), fn ($q) => $q->with('user'))
            ->search($validated['search'] ?? null)
            ->when(
                isset($validated['status']),
                fn ($q) => $q->where('status', $validated['status'])
            );

        $sort = $validated['sort'] ?? 'due_date';
        $direction = $validated['direction'] ?? 'asc';

        // Keep NULL due dates last regardless of direction, then apply the sort.
        if ($sort === 'due_date') {
            $query->orderByRaw('due_date IS NULL')->orderBy('due_date', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }
        $query->orderBy('id', 'desc');

        $tasks = $query->paginate($validated['per_page'] ?? 10)->withQueryString();

        return TaskResource::collection($tasks);
    }

    public function store(StoreTaskRequest $request): TaskResource
    {
        $this->authorize('create', Task::class);

        $task = $request->user()->tasks()->create($request->validated());

        return new TaskResource($task);
    }

    public function show(Task $task): TaskResource
    {
        $this->authorize('view', $task);

        return new TaskResource($task);
    }

    public function update(UpdateTaskRequest $request, Task $task): TaskResource
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return new TaskResource($task);
    }

    public function destroy(Task $task): Response
    {
        $this->authorize('delete', $task);

        $task->delete();

        return response()->noContent();
    }
}
