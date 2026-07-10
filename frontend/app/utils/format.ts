import type { TaskStatus } from '~/types'

export const statusLabels: Record<TaskStatus, string> = {
  pending: 'Ожидает',
  in_progress: 'В работе',
  completed: 'Выполнено',
}

export function statusLabel(status: TaskStatus): string {
  return statusLabels[status] ?? status
}

/**
 * Russian pluralization. `forms` is [one, few, many],
 * e.g. pluralRu(n, ['задача', 'задачи', 'задач']).
 */
export function pluralRu(n: number, forms: [string, string, string]): string {
  const mod10 = n % 10
  const mod100 = n % 100
  if (mod10 === 1 && mod100 !== 11) return forms[0]
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return forms[1]
  return forms[2]
}

/**
 * Format an ISO date (YYYY-MM-DD) into a readable label.
 * Returns an em dash when there is no date.
 */
export function formatDate(value: string | null | undefined): string {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '—'
  return date.toLocaleDateString('ru-RU', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

/**
 * True when a task is past its due date and not yet completed.
 */
export function isOverdue(dueDate: string | null, status: TaskStatus): boolean {
  if (!dueDate || status === 'completed') return false
  const due = new Date(dueDate)
  if (Number.isNaN(due.getTime())) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return due < today
}
