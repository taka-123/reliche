import { vi } from 'vitest'

// Setup global mocks for Nuxt auto-imports in test environment
const mockPost = vi.fn()
const mockGet = vi.fn()
const mockPut = vi.fn()
const mockDelete = vi.fn()

// Mock useApi globally for test environment
globalThis.useApi = vi.fn(() => ({
  post: mockPost,
  get: mockGet,
  put: mockPut,
  delete: mockDelete,
}))

// Export mocks for use in tests
export { mockDelete, mockGet, mockPost, mockPut }
