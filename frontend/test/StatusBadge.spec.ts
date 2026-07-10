import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'
import StatusBadge from '~/components/StatusBadge.vue'

describe('StatusBadge.vue', () => {
  it('renders the human label and status modifier class', () => {
    const wrapper = mount(StatusBadge, { props: { status: 'in_progress' } })
    expect(wrapper.text()).toBe('В работе')
    expect(wrapper.classes()).toContain('badge--in_progress')
  })
})
