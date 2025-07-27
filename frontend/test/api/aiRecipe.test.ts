import { describe, it, expect, vi, beforeEach } from 'vitest'
import type { AIRecipeResponse } from '~/types/aiRecipe'

// useApi composableをモック
const mockPost = vi.fn()
vi.mock('~/composables/useApi', () => ({
  default: () => ({
    post: mockPost,
  }),
  useApi: () => ({
    post: mockPost,
  }),
}))

// useRecipeApiをテスト用にインポート

describe('AI Recipe API', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('generateBasicRecipe', () => {
    it('基本レシピ生成APIを正しく呼び出す', async () => {
      // useRecipeApiを動的にインポート
      const { useRecipeApi } = await import('~/composables/useRecipeApi')
      const mockResponse: AIRecipeResponse = {
        success: true,
        data: {
          recipe: {
            title: 'テスト和食レシピ',
            cooking_time: 30,
            servings: 2,
            calories: 400,
            tags: ['和食', '簡単'],
            category: '和食',
            instructions: ['手順1', '手順2', '手順3'],
          },
          recipe_ingredients: [
            {
              name: 'テスト食材',
              amount: '100g',
              nutrition_notes: 'テスト栄養メモ',
              cooking_method_tips: 'テスト調理コツ',
            },
          ],
        },
        message: 'レシピを生成しました',
      }

      mockPost.mockResolvedValue({ data: mockResponse })

      const { generateBasicRecipe } = useRecipeApi()
      const result = await generateBasicRecipe({ category: '和食' })

      expect(mockPost).toHaveBeenCalledWith('/ai-recipes/generate', {
        category: '和食',
      })
      expect(result).toEqual(mockResponse)
    })

    it('デフォルトオプションで基本レシピ生成APIを呼び出す', async () => {
      const mockResponse: AIRecipeResponse = {
        success: true,
        data: {
          recipe: {
            title: 'デフォルトレシピ',
            cooking_time: 25,
            servings: 2,
            calories: 350,
            tags: ['簡単'],
            category: '和食',
            instructions: ['手順1'],
          },
          recipe_ingredients: [],
        },
        message: 'レシピを生成しました',
      }

      mockPost.mockResolvedValue({ data: mockResponse })

      const { generateBasicRecipe } = useRecipeApi()
      const result = await generateBasicRecipe()

      expect(mockPost).toHaveBeenCalledWith('/ai-recipes/generate', {})
      expect(result).toEqual(mockResponse)
    })
  })

  describe('generateRecipeByIngredients', () => {
    it('食材指定レシピ生成APIを正しく呼び出す', async () => {
      const mockResponse: AIRecipeResponse = {
        success: true,
        data: {
          recipe: {
            title: '豚キャベツ炒め',
            cooking_time: 15,
            servings: 2,
            calories: 350,
            tags: ['時短', '簡単'],
            category: '中華',
            instructions: ['手順1', '手順2'],
          },
          recipe_ingredients: [
            {
              name: '豚肉',
              amount: '200g',
            },
            {
              name: 'キャベツ',
              amount: '1/4玉',
            },
          ],
        },
        message: '指定食材を使ったレシピを生成しました',
      }

      mockPost.mockResolvedValue({ data: mockResponse })

      const { generateRecipeByIngredients } = useRecipeApi()
      const result = await generateRecipeByIngredients({
        ingredients: ['豚肉', 'キャベツ'],
        save_to_db: false,
      })

      expect(mockPost).toHaveBeenCalledWith(
        '/ai-recipes/generate/ingredients',
        {
          ingredients: ['豚肉', 'キャベツ'],
          save_to_db: false,
        }
      )
      expect(result).toEqual(mockResponse)
    })
  })

  describe('generateRecipeWithConstraints', () => {
    it('制約条件付きレシピ生成APIを正しく呼び出す', async () => {
      const mockResponse: AIRecipeResponse = {
        success: true,
        data: {
          recipe: {
            title: '時短パスタ',
            cooking_time: 15,
            servings: 1,
            calories: 500,
            tags: ['時短', 'パスタ'],
            category: 'イタリアン',
            instructions: ['手順1', '手順2'],
          },
          recipe_ingredients: [],
        },
        message: '制約条件を満たすレシピを生成しました',
      }

      mockPost.mockResolvedValue({ data: mockResponse })

      const { generateRecipeWithConstraints } = useRecipeApi()
      const result = await generateRecipeWithConstraints({
        max_time: 20,
        tags: ['時短'],
        difficulty: '簡単',
        save_to_db: true,
      })

      expect(mockPost).toHaveBeenCalledWith(
        '/ai-recipes/generate/constraints',
        {
          max_time: 20,
          tags: ['時短'],
          difficulty: '簡単',
          save_to_db: true,
        }
      )
      expect(result).toEqual(mockResponse)
    })

    it('デフォルトオプションで制約条件付きレシピ生成APIを呼び出す', async () => {
      const mockResponse: AIRecipeResponse = {
        success: true,
        data: {
          recipe: {
            title: 'デフォルト制約レシピ',
            cooking_time: 30,
            servings: 2,
            calories: 400,
            tags: ['簡単'],
            category: '和食',
            instructions: ['手順1'],
          },
          recipe_ingredients: [],
        },
        message: '制約条件を満たすレシピを生成しました',
      }

      mockPost.mockResolvedValue({ data: mockResponse })

      const { generateRecipeWithConstraints } = useRecipeApi()
      const result = await generateRecipeWithConstraints()

      expect(mockPost).toHaveBeenCalledWith(
        '/ai-recipes/generate/constraints',
        {}
      )
      expect(result).toEqual(mockResponse)
    })
  })

  describe('API Error Handling', () => {
    it('APIエラーを適切に処理する', async () => {
      const mockError = new Error('API Error')
      mockPost.mockRejectedValue(mockError)

      const { generateBasicRecipe } = useRecipeApi()

      await expect(generateBasicRecipe()).rejects.toThrow('API Error')
      expect(mockPost).toHaveBeenCalledWith('/ai-recipes/generate', {})
    })
  })

  describe('HTTP Path Validation', () => {
    it('正しいAPIパスを使用している', async () => {
      const mockResponse: AIRecipeResponse = {
        success: true,
        data: {
          recipe: {
            title: 'パステスト',
            cooking_time: 10,
            servings: 1,
            calories: 200,
            tags: ['テスト'],
            category: 'テスト',
            instructions: ['テスト'],
          },
          recipe_ingredients: [],
        },
        message: 'テスト成功',
      }

      mockPost.mockResolvedValue({ data: mockResponse })

      const {
        generateBasicRecipe,
        generateRecipeByIngredients,
        generateRecipeWithConstraints,
      } = useRecipeApi()

      // 基本生成
      await generateBasicRecipe()
      expect(mockPost).toHaveBeenCalledWith('/ai-recipes/generate', {})

      // 食材指定
      await generateRecipeByIngredients({ ingredients: ['test'] })
      expect(mockPost).toHaveBeenCalledWith(
        '/ai-recipes/generate/ingredients',
        {
          ingredients: ['test'],
        }
      )

      // 制約条件
      await generateRecipeWithConstraints()
      expect(mockPost).toHaveBeenCalledWith(
        '/ai-recipes/generate/constraints',
        {}
      )

      // 正しいパスが使用されていることを確認
      expect(mockPost).toHaveBeenCalledTimes(3)

      // 古い間違ったパスが使用されていないことを確認
      expect(mockPost).not.toHaveBeenCalledWith(
        '/recipes/generate',
        expect.any(Object)
      )
      expect(mockPost).not.toHaveBeenCalledWith(
        '/recipes/generate/ingredients',
        expect.any(Object)
      )
      expect(mockPost).not.toHaveBeenCalledWith(
        '/recipes/generate/constraints',
        expect.any(Object)
      )
    })
  })
})
