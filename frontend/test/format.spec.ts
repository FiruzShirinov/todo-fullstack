import { describe, expect, it } from 'vitest'
import { formatDate, isOverdue, pluralRu, statusLabel } from '~/utils/format'

describe('statusLabel', () => {
  it('maps enum values to human labels', () => {
    expect(statusLabel('in_progress')).toBe('В работе')
    expect(statusLabel('completed')).toBe('Выполнено')
  })
})

describe('pluralRu', () => {
  const forms: [string, string, string] = ['задача', 'задачи', 'задач']

  it('uses the singular form for 1, 21, 31', () => {
    expect(pluralRu(1, forms)).toBe('задача')
    expect(pluralRu(21, forms)).toBe('задача')
  })

  it('uses the few form for 2-4, 22-24', () => {
    expect(pluralRu(2, forms)).toBe('задачи')
    expect(pluralRu(23, forms)).toBe('задачи')
  })

  it('uses the many form for 0, 5-20, 11-14', () => {
    expect(pluralRu(0, forms)).toBe('задач')
    expect(pluralRu(5, forms)).toBe('задач')
    expect(pluralRu(11, forms)).toBe('задач')
    expect(pluralRu(14, forms)).toBe('задач')
  })
})

describe('formatDate', () => {
  it('returns an em dash for empty input', () => {
    expect(formatDate(null)).toBe('—')
    expect(formatDate('')).toBe('—')
  })

  it('formats a valid ISO date', () => {
    expect(formatDate('2026-08-01')).not.toBe('—')
  })
})

describe('isOverdue', () => {
  it('is false when completed regardless of date', () => {
    expect(isOverdue('2000-01-01', 'completed')).toBe(false)
  })

  it('is true for a past due date on an open task', () => {
    expect(isOverdue('2000-01-01', 'pending')).toBe(true)
  })

  it('is false when there is no due date', () => {
    expect(isOverdue(null, 'pending')).toBe(false)
  })
})
