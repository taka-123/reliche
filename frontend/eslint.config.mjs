import { createConfigForNuxt } from '@nuxt/eslint-config/flat'

export default createConfigForNuxt({
  // Nuxt 3プロジェクト向けの最適化された設定
  features: {
    // Stylisticルールを有効化（フォーマット関連）
    stylistic: true,
  },
}).override('nuxt/typescript/rules', {
  rules: {
    // Nuxt 3のauto-importと相性が悪い未使用変数エラーを緩和
    '@typescript-eslint/no-unused-vars': [
      'error',
      {
        args: 'all',
        argsIgnorePattern: '^_',
        caughtErrors: 'all',
        caughtErrorsIgnorePattern: '^_',
        destructuredArrayIgnorePattern: '^_',
        varsIgnorePattern: '^_',
        ignoreRestSiblings: true,
      },
    ],
    // 動的削除を許可（register.vueなど）
    '@typescript-eslint/no-dynamic-delete': 'warn',
    // 空オブジェクト型を許可（型定義ファイルなど）
    '@typescript-eslint/no-empty-object-type': 'off',
    // any型の使用を警告レベルに（型定義ファイルなど）
    '@typescript-eslint/no-explicit-any': 'warn',
    // ts-nocheckコメントを警告レベルに
    '@typescript-eslint/ban-ts-comment': 'warn',
  },
})
