import type { FullConfig } from '@playwright/test'

function globalSetup(_config: FullConfig) {
  // グローバルセットアップ処理

  console.log('Playwright E2Eテスト開始')

  // テスト環境の初期化処理があればここに記述
  // 例: テストデータベースの準備、認証トークンの設定など

  return () => {
    // クリーンアップ処理

    console.log('Playwright E2Eテスト終了')
  }
}

export default globalSetup
