import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import TaskForm from '~/components/TaskForm.vue'

describe('TaskForm.vue', () => {
  it('shows a validation error and does not emit submit when title is empty', async () => {
    const wrapper = mount(TaskForm)

    await wrapper.find('form').trigger('submit')

    expect(wrapper.find('[data-test="error-title"]').exists()).toBe(true)
    expect(wrapper.emitted('submit')).toBeUndefined()
  })

  it('emits submit with the entered values when valid', async () => {
    const wrapper = mount(TaskForm)

    await wrapper.find('#task-title').setValue('Write the report')
    await wrapper.find('#task-due').setValue('2026-09-15')
    await wrapper.find('#task-status').setValue('in_progress')
    await wrapper.find('form').trigger('submit')

    const submitted = wrapper.emitted('submit')
    expect(submitted).toHaveLength(1)
    expect(submitted![0][0]).toMatchObject({
      title: 'Write the report',
      due_date: '2026-09-15',
      status: 'in_progress',
    })
  })

  it('pre-fills fields when editing an existing task', () => {
    const wrapper = mount(TaskForm, {
      props: {
        task: {
          id: 1,
          user_id: 1,
          title: 'Existing task',
          description: 'desc',
          due_date: '2026-01-01',
          status: 'completed',
          created_at: '',
          updated_at: '',
          can: { update: true, delete: true },
        },
      },
    })

    expect((wrapper.find('#task-title').element as HTMLInputElement).value).toBe('Existing task')
    expect(wrapper.find('button[type="submit"]').text()).toContain('Сохранить')
  })
})
