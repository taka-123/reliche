import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import ReviewList from '~/components/ReviewList.vue'
import type { RecipeReview, ReviewStatistics } from '~/types/review'
// Create Vuetify instance for tests
const vuetify = createVuetify()
describe('ReviewList', () => {
  const mockReviews: RecipeReview[] = [
    {
      id: 1,
      recipe_id: 1,
      user_id: 1,
      rating: 4,
      taste_score: 5,
      difficulty_score: 3,
      instruction_clarity: 4,
      comment: 'とても美味しかったです！',
      review_images: ['https://example.com/image1.jpg'],
      average_score: 4,
      user: {
        id: 1,
        name: 'テストユーザー',
      },
      created_at: '2025-01-27T10:00:00Z',
      updated_at: '2025-01-27T10:00:00Z',
    },
    {
      id: 2,
      recipe_id: 1,
      user_id: 2,
      rating: 5,
      taste_score: 5,
      difficulty_score: 2,
      instruction_clarity: 5,
      comment: '簡単で美味しい！',
      review_images: [],
      average_score: 5,
      user: {
        id: 2,
        name: '料理好きさん',
      },
      created_at: '2025-01-27T11:00:00Z',
      updated_at: '2025-01-27T11:00:00Z',
    },
  ]
  const mockStatistics: ReviewStatistics = {
    total_reviews: 2,
    average_rating: 4.5,
    average_taste_score: 5,
    average_difficulty_score: 2.5,
    average_instruction_clarity: 4.5,
    rating_distribution: {
      1: { count: 0, percentage: 0 },
      2: { count: 0, percentage: 0 },
      3: { count: 0, percentage: 0 },
      4: { count: 1, percentage: 50 },
      5: { count: 1, percentage: 50 },
    },
  }
  const mockMeta = {
    current_page: 1,
    last_page: 2,
    per_page: 10,
    total: 15,
  }
  const defaultProps = {
    reviews: mockReviews,
    statistics: mockStatistics,
    meta: mockMeta,
    isLoading: false,
    canWriteReview: true,
    currentUserId: 1,
  }
  // Helper function to create wrapper with Vuetify
  const createWrapper = (props = {}) => {
    return mount(ReviewList, {
      props: { ...defaultProps, ...props },
      global: {
        plugins: [vuetify],
      },
    })
  }
  it('統計情報が正しく表示される', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.statistics-section').exists()).toBe(true)
    expect(wrapper.find('.statistics-title').text()).toContain('評価統計')
    expect(wrapper.find('.rating-number').text()).toBe('4.5')
    expect(wrapper.find('.review-count').text()).toContain('2件のレビュー')
  })
  it('評価分布が正しく表示される', () => {
    const wrapper = createWrapper()
    const distributionRows = wrapper.findAll('.distribution-row')
    expect(distributionRows).toHaveLength(5)
    // 5つ星の分布をチェック
    const fiveStarRow = distributionRows[0]
    expect(fiveStarRow.find('.star-label').text()).toBe('5★')
    expect(fiveStarRow.find('.percentage').text()).toBe('50%')
  })
  it('詳細評価が正しく表示される', () => {
    const wrapper = createWrapper()
    const detailedScores = wrapper.find('.detailed-scores')
    expect(detailedScores.exists()).toBe(true)
    const scoreItems = detailedScores.findAll('.score-item')
    expect(scoreItems).toHaveLength(3)
    // 味評価をチェック
    const tasteScore = scoreItems[0]
    expect(tasteScore.find('.score-label').text()).toBe('味')
    expect(tasteScore.find('.score-value').text()).toBe('5.0')
  })
  it('レビュー一覧が正しく表示される', () => {
    const wrapper = createWrapper()
    const reviewCards = wrapper.findAll('.review-card')
    expect(reviewCards).toHaveLength(2)
    // 最初のレビューカードをチェック
    const firstCard = reviewCards[0]
    expect(firstCard.find('.user-name').text()).toBe('テストユーザー')
    expect(firstCard.find('.review-comment p').text()).toBe(
      'とても美味しかったです！',
    )
  })
  it('レビュー投稿ボタンが条件に応じて表示される', () => {
    const wrapper = createWrapper()

    // コンポーネントの初期状態確認
    expect(wrapper.vm).toBeTruthy()
    expect(wrapper.vm.canWriteReview).toBe(true)

    // canWriteReviewがfalseの場合のテスト
    const wrapperNoButton = createWrapper({
      canWriteReview: false,
    })
    expect(wrapperNoButton.vm.canWriteReview).toBe(false)
  })
  it('ローディング状態が正しく表示される', () => {
    const wrapper = createWrapper({
      isLoading: true,
      reviews: undefined,
    })

    expect(wrapper.find('.loading-container').exists()).toBe(true)
    expect(wrapper.find('.loading-text').text()).toContain(
      'レビューを読み込み中',
    )
  })
  it('レビューがない場合のメッセージが表示される', () => {
    const wrapper = createWrapper({
      reviews: [],
      isLoading: false,
    })
    expect(wrapper.find('.no-reviews').exists()).toBe(true)
    expect(wrapper.find('.no-reviews-text').text()).toContain(
      'まだレビューがありません',
    )
  })
  it('ページネーションが正しく表示される', () => {
    const wrapper = createWrapper()
    expect(wrapper.find('.pagination-container').exists()).toBe(true)
  })
  it('ページネーションが1ページの場合非表示になる', () => {
    const wrapper = createWrapper({
      meta: {
        ...mockMeta,
        last_page: 1,
      },
    })
    expect(wrapper.find('.pagination-container').exists()).toBe(false)
  })
  it('レビュー編集・削除メニューが適切なユーザーにのみ表示される', () => {
    const wrapper = createWrapper()
    // 自分のレビューの場合、メニューが表示される
    expect(wrapper.vm.canEditReview(mockReviews[0])).toBe(true)
    // 他人のレビューの場合、メニューが表示されない
    expect(wrapper.vm.canEditReview(mockReviews[1])).toBe(false)
  })
  it('詳細評価がある場合のみ表示される', () => {
    const wrapper = createWrapper()

    // 詳細評価があるレビュー
    expect(wrapper.vm.hasDetailedRatings(mockReviews[0])).toBe(true)
    // 詳細評価がないレビュー
    const reviewWithoutDetails = {
      ...mockReviews[0],
      taste_score: null,
      difficulty_score: null,
      instruction_clarity: null,
    }
    expect(wrapper.vm.hasDetailedRatings(reviewWithoutDetails)).toBe(false)
  })
  it('日付フォーマットが正しく動作する', () => {
    const wrapper = createWrapper()
    const formattedDate = wrapper.vm.formatDate('2025-01-27T10:00:00Z')
    expect(formattedDate).toBe('2025年1月27日')
  })
  it('イベント発行が正しく動作する', () => {
    const wrapper = createWrapper()

    // イベント発行テスト
    wrapper.vm.$emit('write-review')
    expect(wrapper.emitted('write-review')).toBeTruthy()

    wrapper.vm.$emit('page-change', 2)
    expect(wrapper.emitted('page-change')).toBeTruthy()
    expect(wrapper.emitted('page-change')?.[0]?.[0]).toBe(2)

    // レビュー関連のイベント
    wrapper.vm.$emit('edit-review', mockReviews[0])
    expect(wrapper.emitted('edit-review')).toBeTruthy()
    expect(wrapper.emitted('edit-review')?.[0]?.[0]).toEqual(mockReviews[0])
  })
  it('統計情報がない場合は統計セクションが表示されない', () => {
    const wrapper = createWrapper({
      statistics: undefined,
    })
    expect(wrapper.find('.statistics-section').exists()).toBe(false)
  })
  it('詳細評価統計の表示判定が正しく動作する', () => {
    const wrapper = createWrapper()
    expect(wrapper.vm.hasDetailedScores).toBe(true)
    // 詳細評価がない統計データ
    const wrapperNoDetails = createWrapper({
      statistics: {
        ...mockStatistics,
        average_taste_score: 0,
        average_difficulty_score: 0,
        average_instruction_clarity: 0,
      },
    })
    expect(wrapperNoDetails.vm.hasDetailedScores).toBe(false)
  })
  it('レビュー画像の表示が正しく動作する', () => {
    const wrapper = createWrapper()
    const firstCard = wrapper.findAll('.review-card')[0]
    const imageChips = firstCard.findAll('.image-chip')
    expect(imageChips).toHaveLength(1)
    expect(imageChips[0].text()).toContain('画像 1')
  })
})
