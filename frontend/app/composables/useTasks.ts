import type { Paginated, Task, TaskFilters, TaskInput } from '~/types'

/**
 * Thin wrapper around the tasks REST endpoints. State (list, loading, errors)
 * is owned by the page via useAsyncData; this composable only performs calls.
 */
export function useTasks() {
  const api = useApi()

  function list(filters: Partial<TaskFilters> & { per_page?: number }) {
    const query: Record<string, string | number> = {}
    if (filters.search) query.search = filters.search
    if (filters.status) query.status = filters.status
    if (filters.sort) query.sort = filters.sort
    if (filters.direction) query.direction = filters.direction
    if (filters.page) query.page = filters.page
    if (filters.per_page) query.per_page = filters.per_page

    return api<Paginated<Task>>('/tasks', { query })
  }

  function create(input: TaskInput) {
    return api<{ data: Task }>('/tasks', { method: 'POST', body: input })
  }

  function update(id: number, input: Partial<TaskInput>) {
    return api<{ data: Task }>(`/tasks/${id}`, { method: 'PATCH', body: input })
  }

  function remove(id: number) {
    return api(`/tasks/${id}`, { method: 'DELETE' })
  }

  return { list, create, update, remove }
}
