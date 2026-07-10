import { describe, expect, it } from 'vitest'
import {
  isFormValid,
  validateLoginForm,
  validateTaskForm,
} from '~/utils/validation'

describe('validateTaskForm', () => {
  const base = { title: 'Valid title', description: '', due_date: '', status: 'pending' }

  it('accepts a valid task', () => {
    const errors = validateTaskForm(base)
    expect(isFormValid(errors)).toBe(true)
  })

  it('requires a title', () => {
    const errors = validateTaskForm({ ...base, title: '   ' })
    expect(errors.title).toBeDefined()
  })

  it('rejects a title shorter than 3 characters', () => {
    const errors = validateTaskForm({ ...base, title: 'ab' })
    expect(errors.title).toContain('минимум 3')
  })

  it('rejects an invalid date', () => {
    const errors = validateTaskForm({ ...base, due_date: 'not-a-date' })
    expect(errors.due_date).toBeDefined()
  })

  it('accepts a valid date', () => {
    const errors = validateTaskForm({ ...base, due_date: '2026-08-01' })
    expect(errors.due_date).toBeUndefined()
  })

  it('rejects an unknown status', () => {
    const errors = validateTaskForm({ ...base, status: 'archived' })
    expect(errors.status).toBeDefined()
  })
})

describe('validateLoginForm', () => {
  it('requires email and password', () => {
    const errors = validateLoginForm({ email: '', password: '' })
    expect(errors.email).toBeDefined()
    expect(errors.password).toBeDefined()
  })

  it('rejects a malformed email', () => {
    const errors = validateLoginForm({ email: 'nope', password: 'x' })
    expect(errors.email).toBeDefined()
  })

  it('accepts valid credentials shape', () => {
    const errors = validateLoginForm({ email: 'a@b.com', password: 'secret' })
    expect(isFormValid(errors)).toBe(true)
  })
})
