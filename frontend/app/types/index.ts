export type TaskStatus = 'pending' | 'in_progress' | 'completed'

export type UserRole = 'user' | 'viewer' | 'admin'

export interface User {
  id: number
  name: string
  email: string
  role: UserRole
}

export interface Task {
  id: number
  user_id: number
  title: string
  description: string | null
  due_date: string | null
  status: TaskStatus
  created_at: string
  updated_at: string
  user?: User
  can: {
    update: boolean
    delete: boolean
  }
}

export interface PaginationMeta {
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface Paginated<T> {
  data: T[]
  meta: PaginationMeta
  links: Record<string, string | null>
}

export interface TaskFilters {
  search: string
  status: TaskStatus | ''
  sort: 'due_date' | 'status' | 'title' | 'created_at'
  direction: 'asc' | 'desc'
  page: number
}

export interface TaskInput {
  title: string
  description: string
  due_date: string
  status: TaskStatus
}
