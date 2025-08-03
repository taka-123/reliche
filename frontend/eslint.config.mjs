import { createConfigForNuxt } from '@nuxt/eslint-config/flat'

export default createConfigForNuxt({
  // Nuxt 3プロジェクト向けの最適化された設定
  features: {
    // Stylisticルールを有効化（フォーマット関連）
    stylistic: true,
  },
})
