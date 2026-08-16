import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, h, nextTick } from 'vue'
import FiveStateContainer from '../../src/components/FiveStateContainer.vue'
import ApiErrorBoundary from '../../src/components/ApiErrorBoundary.vue'
import RestrictedState from '../../src/components/RestrictedState.vue'
import UnknownResult from '../../src/components/UnknownResult.vue'

describe('五态组件', () => {
  it('FiveStateContainer: empty 态渲染', () => {
    const w = mount(FiveStateContainer, { props: { state: 'empty' } })
    expect(w.find('[data-testid="fs-empty"]').exists()).toBe(true)
  })

  it('FiveStateContainer: error 态显示信息并触发 retry', async () => {
    const w = mount(FiveStateContainer, { props: { state: 'error', errorMessage: 'boom' } })
    expect(w.text()).toContain('boom')
    await w.find('.fs-retry').trigger('click')
    expect(w.emitted('retry')).toBeTruthy()
  })

  it('FiveStateContainer: default 态渲染默认插槽', () => {
    const w = mount(FiveStateContainer, {
      props: { state: 'default' },
      slots: { default: '<p data-testid="slot">hello</p>' },
    })
    expect(w.find('[data-testid="slot"]').exists()).toBe(true)
  })

  it('ApiErrorBoundary: 无错误时渲染插槽', () => {
    const w = mount(ApiErrorBoundary, {
      slots: { default: '<p data-testid="ok">ok</p>' },
    })
    expect(w.find('[data-testid="ok"]').exists()).toBe(true)
    expect(w.find('[role="alert"]').exists()).toBe(false)
  })

  it('ApiErrorBoundary: 捕获子组件异常并显示兜底', async () => {
    const Boom = defineComponent({
      setup() {
        return () => {
          throw new Error('child boom')
        }
      },
    })
    const w = mount(ApiErrorBoundary, { slots: { default: () => h(Boom) } })
    await nextTick()
    expect(w.find('[role="alert"]').exists()).toBe(true)
    expect(w.text()).toContain('child boom')
  })

  it('RestrictedState: 显示 reason 与 nextStep', () => {
    const w = mount(RestrictedState, {
      props: { reason: 'KYC_REQUIRED', nextStep: '请上传证件' },
    })
    expect(w.text()).toContain('KYC_REQUIRED')
    expect(w.text()).toContain('请上传证件')
  })

  it('UnknownResult: 显示 idempotencyKey 并触发 query', async () => {
    const w = mount(UnknownResult, { props: { idempotencyKey: 'IK-1' } })
    expect(w.text()).toContain('IK-1')
    await w.find('.ur-query').trigger('click')
    expect(w.emitted('query')).toBeTruthy()
  })
})
