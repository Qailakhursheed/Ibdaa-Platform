import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import StatCard from '@/components/common/StatCard.vue'

describe('StatCard', () => {
  it('renders title and value correctly', () => {
    const wrapper = mount(StatCard, {
      props: {
        title: 'إجمالي الطلاب',
        value: 245,
        icon: 'users',
        color: 'blue'
      }
    })

    expect(wrapper.text()).toContain('إجمالي الطلاب')
    expect(wrapper.text()).toContain('245')
  })

  it('applies correct color class', () => {
    const wrapper = mount(StatCard, {
      props: {
        title: 'Test',
        value: 100,
        icon: 'users',
        color: 'green'
      }
    })

    expect(wrapper.html()).toContain('text-green-600')
  })

  it('displays trend when provided', () => {
    const wrapper = mount(StatCard, {
      props: {
        title: 'Test',
        value: 100,
        icon: 'users',
        color: 'blue',
        trend: 12
      }
    })

    expect(wrapper.text()).toContain('12%')
  })
})
