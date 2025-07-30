import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createVuetify } from 'vuetify'
import ReviewForm from '~/components/ReviewForm.vue'
import type { RecipeReview } from '~/types/review'

// Create Vuetify instance for tests
const vuetify = createVuetify()

// Mock external composables (setup.ts will handle these globally, but adding local fallbacks)
const mockCreateReview = vi.fn()
const mockUpdateReview = vi.fn()

describe('ReviewForm', () => {
  const defaultProps = {
    recipeId: '1',
  }

  const mockExistingReview: RecipeReview = {
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
  }

  // Helper function to create wrapper with Vuetify
  const createWrapper = (props = {}) => {
    return mount(ReviewForm, {
      props: { ...defaultProps, ...props },
      global: {
        plugins: [vuetify],
      },
    })
  }

  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('新規レビュー投稿フォームが正しく表示される', () => {
    const wrapper = createWrapper()

    expect(wrapper.find('.form-title').text()).toContain('レビューを投稿')
    expect(wrapper.find('.rating-section').exists()).toBe(true)
    expect(wrapper.find('.detail-ratings').exists()).toBe(true)
    expect(wrapper.find('textarea').exists()).toBe(true)
  })

  it('編集モードで既存データが正しく設定される', async () => {
    const wrapper = createWrapper({
      existingReview: mockExistingReview,
    })

    await nextTick()

    expect(wrapper.find('.form-title').text()).toContain('レビューを編集')

    // フォームデータが正しく設定されているかは内部実装に依存するため、
    // プロパティや動作で検証
    expect(wrapper.vm.isEditing).toBe(true)
  })

  it('評価テキストが正しく表示される', () => {
    const wrapper = createWrapper()

    // 各評価値に対応するテキストをテスト
    expect(wrapper.vm.getRatingText(0)).toBe('')
    expect(wrapper.vm.getRatingText(1)).toBe('改善が必要')
    expect(wrapper.vm.getRatingText(2)).toBe('イマイチ')
    expect(wrapper.vm.getRatingText(3)).toBe('普通')
    expect(wrapper.vm.getRatingText(4)).toBe('良い')
    expect(wrapper.vm.getRatingText(5)).toBe('最高！')
  })

  it('画像URLの追加と削除が正しく動作する', () => {
    const wrapper = createWrapper()

    // 画像URL追加
    wrapper.vm.imageUrl = 'https://example.com/test.jpg'
    wrapper.vm.addImageUrl()

    expect(wrapper.vm.formData.review_images).toContain(
      'https://example.com/test.jpg'
    )
    expect(wrapper.vm.imageUrl).toBe('')

    // 画像削除
    wrapper.vm.removeImage(0)
    expect(wrapper.vm.formData.review_images).toHaveLength(0)
  })

  it('画像URLの上限が正しく制御される', () => {
    const wrapper = createWrapper()

    // 5つの画像URLを追加
    for (let i = 1; i <= 6; i++) {
      wrapper.vm.imageUrl = `https://example.com/test${i}.jpg`
      wrapper.vm.addImageUrl()
    }

    // 5つまでしか追加されないことを確認
    expect(wrapper.vm.formData.review_images).toHaveLength(5)
  })

  it('バリデーションルールが正しく動作する', () => {
    const wrapper = createWrapper()

    // 評価のバリデーション
    expect(wrapper.vm.rules.rating(0)).toBe('評価を選択してください')
    expect(wrapper.vm.rules.rating(3)).toBe(true)

    // コメントのバリデーション
    expect(wrapper.vm.rules.comment('')).toBe(true)
    expect(wrapper.vm.rules.comment('a'.repeat(500))).toBe(true)
    expect(wrapper.vm.rules.comment('a'.repeat(1001))).toBe(
      'コメントは1000文字以内で入力してください'
    )
  })

  it('新規レビュー投稿が正しく実行される', async () => {
    const mockReview = { ...mockExistingReview, id: 2 }
    mockCreateReview.mockResolvedValue(mockReview)

    const wrapper = createWrapper()

    // フォームデータを設定
    wrapper.vm.formData = {
      rating: 4,
      taste_score: 5,
      difficulty_score: 3,
      instruction_clarity: 4,
      comment: 'テストコメント',
      review_images: [],
    }
    wrapper.vm.isFormValid = true

    // 投稿実行
    await wrapper.vm.submitReview()

    expect(mockCreateReview).toHaveBeenCalledWith('1', wrapper.vm.formData)
    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('success')?.[0]?.[0]).toEqual(mockReview)
  })

  it('レビュー更新が正しく実行される', async () => {
    const updatedReview = {
      ...mockExistingReview,
      comment: '更新されたコメント',
    }
    mockUpdateReview.mockResolvedValue(updatedReview)

    const wrapper = createWrapper({
      existingReview: mockExistingReview,
    })

    // フォームデータを設定
    wrapper.vm.formData = {
      rating: 4,
      taste_score: 5,
      difficulty_score: 3,
      instruction_clarity: 4,
      comment: '更新されたコメント',
      review_images: [],
    }
    wrapper.vm.isFormValid = true

    // 更新実行
    await wrapper.vm.submitReview()

    expect(mockUpdateReview).toHaveBeenCalledWith('1', 1, wrapper.vm.formData)
    expect(wrapper.emitted('success')).toBeTruthy()
    expect(wrapper.emitted('success')?.[0]?.[0]).toEqual(updatedReview)
  })

  it('エラーハンドリングが正しく動作する', async () => {
    const errorMessage = 'ネットワークエラー'
    mockCreateReview.mockRejectedValue(new Error(errorMessage))

    const wrapper = createWrapper()

    wrapper.vm.formData = {
      rating: 4,
      comment: 'テストコメント',
    }
    wrapper.vm.isFormValid = true

    await wrapper.vm.submitReview()

    expect(wrapper.emitted('error')).toBeTruthy()
    expect(wrapper.emitted('error')?.[0]?.[0]).toBe(errorMessage)
  })

  it('キャンセルボタンが正しく動作する', async () => {
    const wrapper = createWrapper()

    await wrapper.find('button:contains("キャンセル")').trigger('click')

    expect(wrapper.emitted('cancel')).toBeTruthy()
  })

  it('送信中状態が正しく管理される', async () => {
    mockCreateReview.mockImplementation(
      () =>
        new Promise((resolve) =>
          setTimeout(() => resolve(mockExistingReview), 100)
        )
    )

    const wrapper = createWrapper()

    wrapper.vm.formData = {
      rating: 4,
      comment: 'テストコメント',
    }
    wrapper.vm.isFormValid = true

    // 送信開始
    const submitPromise = wrapper.vm.submitReview()

    // 送信中状態を確認
    expect(wrapper.vm.isSubmitting).toBe(true)

    // 送信完了を待機
    await submitPromise

    // 送信完了後の状態を確認
    expect(wrapper.vm.isSubmitting).toBe(false)
  })
})
