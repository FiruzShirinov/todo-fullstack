import type { TaskStatus } from '~/types'

export const TASK_STATUSES: TaskStatus[] = ['pending', 'in_progress', 'completed']

export interface TaskFormValues {
  title: string
  description: string
  due_date: string
  status: string
}

export type TaskFormErrors = Partial<Record<'title' | 'due_date' | 'status', string>>

/**
 * Client-side validation mirroring the backend Form Request rules.
 * Pure function — deliberately free of Nuxt/Vue imports so it can be unit-tested.
 */
export function validateTaskForm(values: TaskFormValues): TaskFormErrors {
  const errors: TaskFormErrors = {}
  const title = (values.title ?? '').trim()

  if (!title) {
    errors.title = 'Укажите заголовок.'
  } else if (title.length < 3) {
    errors.title = 'Заголовок должен содержать минимум 3 символа.'
  } else if (title.length > 255) {
    errors.title = 'Заголовок не должен превышать 255 символов.'
  }

  if (values.due_date) {
    const date = new Date(values.due_date)
    if (Number.isNaN(date.getTime())) {
      errors.due_date = 'Введите корректную дату.'
    }
  }

  if (values.status && !TASK_STATUSES.includes(values.status as TaskStatus)) {
    errors.status = 'Выберите допустимый статус.'
  }

  return errors
}

/** True when an errors object has no entries (works for any form). */
export function isFormValid(errors: Record<string, unknown>): boolean {
  return Object.keys(errors).length === 0
}

export interface LoginFormValues {
  email: string
  password: string
}

export type LoginFormErrors = Partial<Record<'email' | 'password', string>>

export function validateLoginForm(values: LoginFormValues): LoginFormErrors {
  const errors: LoginFormErrors = {}
  const email = (values.email ?? '').trim()

  if (!email) {
    errors.email = 'Укажите email.'
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    errors.email = 'Введите корректный email.'
  }

  if (!values.password) {
    errors.password = 'Укажите пароль.'
  }

  return errors
}
