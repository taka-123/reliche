import { vi } from 'vitest'

// Mock CSS imports
vi.mock('*.css', () => ({}))
vi.mock('*.scss', () => ({}))

// Mock nextTick globally
globalThis.nextTick = vi.fn().mockResolvedValue(undefined)

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

// Mock useAuth for test environment
globalThis.useAuth = vi.fn(() => ({
  user: { value: { id: 1, name: 'Test User' } },
  login: vi.fn(),
  logout: vi.fn(),
  isAuthenticated: { value: true },
}))

// Mock useReviewApi for test environment
globalThis.useReviewApi = vi.fn(() => ({
  createReview: vi.fn().mockResolvedValue({
    success: true,
    data: { id: 1, rating: 5 },
    message: 'Review created successfully',
  }),
  updateReview: vi.fn().mockResolvedValue({
    success: true,
    data: { id: 1, rating: 5 },
    message: 'Review updated successfully',
  }),
  getReviews: vi.fn().mockResolvedValue({
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
  }),
  getStatistics: vi.fn().mockResolvedValue({
    data: {
      total_reviews: 0,
      average_rating: 0,
      rating_distribution: {},
    },
  }),
}))

// Mock useMediaApi for test environment
globalThis.useMediaApi = vi.fn(() => ({
  getRecipeMedia: vi.fn().mockResolvedValue({
    data: [],
    meta: { current_page: 1, last_page: 1, per_page: 20, total: 0 },
  }),
  uploadMedia: vi.fn().mockResolvedValue({
    message: 'Media uploaded successfully',
    data: { id: 1, media_type: 'image' },
  }),
  updateMedia: vi.fn().mockResolvedValue({
    data: { id: 1, description: 'Updated' },
  }),
  deleteMedia: vi.fn().mockResolvedValue({
    message: 'Media deleted successfully',
  }),
  validateFileSize: vi.fn().mockReturnValue({ valid: true }),
  validateFileType: vi.fn().mockReturnValue({ valid: true }),
  getMediaTypeFromFile: vi.fn().mockReturnValue('image'),
}))

// Export mocks for use in tests
export { mockDelete, mockGet, mockPost, mockPut }
