// Test environment global type definitions

declare global {
  // Vue composables and utilities
  var nextTick: () => Promise<void>
  var useApi: () => any
  var useAuth: () => any
  var useReviewApi: () => any
}

export {}
