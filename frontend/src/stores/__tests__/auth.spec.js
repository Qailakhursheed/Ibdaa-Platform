import { describe, it, expect, beforeEach, vi } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

// Mock API
vi.mock('@/api/auth', () => ({
  authApi: {
    login: vi.fn(),
    logout: vi.fn(),
    validateToken: vi.fn(),
    getCurrentUser: vi.fn()
  }
}))

describe('Auth Store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
  })

  it('initializes with correct default state', () => {
    const store = useAuthStore()

    expect(store.user).toBeNull()
    expect(store.token).toBeNull()
    expect(store.isAuthenticated).toBe(false)
    expect(store.loading).toBe(false)
  })

  it('computes user role correctly', () => {
    const store = useAuthStore()
    
    store.user = { role: 'manager', full_name: 'Test User' }
    
    expect(store.userRole).toBe('manager')
    expect(store.isManager).toBe(true)
    expect(store.isTechnical).toBe(false)
    expect(store.isTrainer).toBe(false)
    expect(store.isStudent).toBe(false)
  })

  it('loads stored auth from localStorage', () => {
    const testUser = { id: 1, role: 'manager', full_name: 'Test' }
    const testToken = 'test-token-123'
    
    localStorage.setItem('user', JSON.stringify(testUser))
    localStorage.setItem('token', testToken)

    const store = useAuthStore()
    store.loadStoredAuth()

    expect(store.user).toEqual(testUser)
    expect(store.token).toBe(testToken)
  })
})
