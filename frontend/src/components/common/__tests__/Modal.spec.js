import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import Modal from '@/components/common/Modal.vue'

describe('Modal', () => {
  it('renders when modelValue is true', () => {
    const wrapper = mount(Modal, {
      props: {
        modelValue: true,
        title: 'Test Modal'
      }
    })

    expect(wrapper.isVisible()).toBe(true)
    expect(wrapper.text()).toContain('Test Modal')
  })

  it('does not render when modelValue is false', () => {
    const wrapper = mount(Modal, {
      props: {
        modelValue: false,
        title: 'Test Modal'
      }
    })

    expect(wrapper.find('.fixed').exists()).toBe(false)
  })

  it('emits close event when close button is clicked', async () => {
    const wrapper = mount(Modal, {
      props: {
        modelValue: true,
        title: 'Test Modal'
      }
    })

    await wrapper.find('button').trigger('click')
    
    expect(wrapper.emitted()).toHaveProperty('update:modelValue')
    expect(wrapper.emitted()).toHaveProperty('close')
  })

  it('renders slot content', () => {
    const wrapper = mount(Modal, {
      props: {
        modelValue: true,
        title: 'Test Modal'
      },
      slots: {
        default: '<p>Slot Content</p>'
      }
    })

    expect(wrapper.text()).toContain('Slot Content')
  })
})
