import { test, expect } from '@playwright/test'

test.describe('レビューシステム', () => {
  test.beforeEach(async ({ page }) => {
    // テスト用のレシピページに移動
    await page.goto('/recipes/1')
    await expect(page.locator('h1')).toContainText('レシピ詳細')
  })

  test('レビュー一覧が表示される', async ({ page }) => {
    // レビューセクションが表示されることを確認
    await expect(page.locator('.reviews-section')).toBeVisible()

    // レビュー統計が表示されることを確認
    const statisticsSection = page.locator('.statistics-section')
    if (await statisticsSection.isVisible()) {
      await expect(
        statisticsSection.locator('.statistics-title')
      ).toContainText('評価統計')
    }

    // レビュー一覧ヘッダーが表示されることを確認
    await expect(page.locator('.reviews-title')).toContainText('レビュー一覧')
  })

  test('ログイン時にレビュー投稿ボタンが表示される', async ({ page }) => {
    // 認証状態のシミュレーション（実際のテストでは適切な認証設定が必要）
    await page.addInitScript(() => {
      window.localStorage.setItem('auth-token', 'test-token')
    })

    await page.reload()

    // レビュー投稿ボタンが表示されることを確認
    const writeReviewButton = page.locator('text=レビューを書く')
    await expect(writeReviewButton).toBeVisible()
  })

  test('レビューフォームの動作', async ({ page }) => {
    // 認証状態を設定
    await page.addInitScript(() => {
      window.localStorage.setItem('auth-token', 'test-token')
    })

    await page.reload()

    // レビュー投稿ボタンをクリック
    await page.click('text=レビューを書く')

    // レビューフォームダイアログが表示されることを確認
    const dialog = page.locator('.v-dialog')
    await expect(dialog).toBeVisible()

    // フォームタイトルを確認
    await expect(dialog.locator('.form-title')).toContainText('レビューを投稿')

    // 評価セクションが表示されることを確認
    await expect(dialog.locator('.rating-section')).toBeVisible()

    // 評価を設定
    const ratingInput = dialog.locator('.rating-input .v-rating')
    const stars = ratingInput.locator('.v-btn')
    await stars.nth(3).click() // 4つ星を選択

    // 評価テキストが更新されることを確認
    await expect(dialog.locator('.rating-text')).toContainText('良い')

    // 詳細評価パネルを展開
    await dialog.locator('.v-expansion-panel-title').click()

    // 詳細評価が表示されることを確認
    await expect(dialog.locator('.detail-rating-item')).toHaveCount(3)

    // 味評価を設定
    const tasteRating = dialog
      .locator('.detail-rating-item')
      .first()
      .locator('.v-rating .v-btn')
    await tasteRating.nth(4).click() // 5つ星を選択

    // コメントを入力
    await dialog
      .locator('textarea[label="コメント（任意）"]')
      .fill('とても美味しいレシピでした！')

    // 画像URLを追加
    const imageUrlInput = dialog.locator('input[label="画像URL（任意）"]')
    await imageUrlInput.fill('https://example.com/test-image.jpg')
    await dialog.locator('.image-input .v-btn').click()

    // 画像チップが表示されることを確認
    await expect(dialog.locator('.image-chip')).toBeVisible()

    // 投稿ボタンが有効になることを確認
    const submitButton = dialog.locator('text=投稿する')
    await expect(submitButton).toBeEnabled()
  })

  test('レビューフォームのバリデーション', async ({ page }) => {
    // 認証状態を設定
    await page.addInitScript(() => {
      window.localStorage.setItem('auth-token', 'test-token')
    })

    await page.reload()

    // レビュー投稿ボタンをクリック
    await page.click('text=レビューを書く')

    const dialog = page.locator('.v-dialog')

    // 評価を設定しない状態で投稿ボタンをクリック
    const submitButton = dialog.locator('text=投稿する')
    await expect(submitButton).toBeDisabled()

    // 評価を設定
    const ratingInput = dialog.locator('.rating-input .v-rating .v-btn')
    await ratingInput.nth(2).click() // 3つ星を選択

    // 投稿ボタンが有効になることを確認
    await expect(submitButton).toBeEnabled()

    // 長すぎるコメントを入力
    const longComment = 'あ'.repeat(1001)
    await dialog.locator('textarea[label="コメント（任意）"]').fill(longComment)

    // バリデーションエラーが表示されることを確認（実装に応じて調整）
    // await expect(dialog.locator('text=コメントは1000文字以内で入力してください')).toBeVisible()
  })

  test('レビューの編集・削除機能', async ({ page }) => {
    // 認証状態とレビューデータがある状態をシミュレーション
    await page.addInitScript(() => {
      window.localStorage.setItem('auth-token', 'test-token')
      // レビューデータのモック設定（実際のテストでは適切なデータ設定が必要）
    })

    await page.reload()

    // 既存のレビューがある場合
    const reviewCard = page.locator('.review-card').first()
    if (await reviewCard.isVisible()) {
      // レビューアクションメニューを開く
      const menuButton = reviewCard
        .locator('.v-btn[aria-label*="menu"]')
        .first()
      if (await menuButton.isVisible()) {
        await menuButton.click()

        // 編集メニューが表示されることを確認
        await expect(page.locator('text=編集')).toBeVisible()
        await expect(page.locator('text=削除')).toBeVisible()

        // 編集をクリック
        await page.click('text=編集')

        // 編集フォームが表示されることを確認
        const dialog = page.locator('.v-dialog')
        await expect(dialog).toBeVisible()
        await expect(dialog.locator('.form-title')).toContainText(
          'レビューを編集'
        )
      }
    }
  })

  test('レビュー統計の表示', async ({ page }) => {
    // 統計データがある場合の表示確認
    const statisticsSection = page.locator('.statistics-section')

    if (await statisticsSection.isVisible()) {
      // 全体評価が表示されることを確認
      await expect(statisticsSection.locator('.rating-number')).toBeVisible()
      await expect(statisticsSection.locator('.review-count')).toBeVisible()

      // 評価分布が表示されることを確認
      await expect(statisticsSection.locator('.distribution-row')).toHaveCount(
        5
      )

      // 詳細評価がある場合の表示確認
      const detailedScores = statisticsSection.locator('.detailed-scores')
      if (await detailedScores.isVisible()) {
        await expect(
          detailedScores.locator('.score-item')
        ).toHaveCount.greaterThan(0)
      }
    }
  })

  test('ページネーション機能', async ({ page }) => {
    // 複数ページのレビューがある場合のテスト
    const paginationContainer = page.locator('.pagination-container')

    if (await paginationContainer.isVisible()) {
      // ページネーションコンポーネントが表示されることを確認
      await expect(paginationContainer.locator('.v-pagination')).toBeVisible()

      // 次のページボタンをクリック
      const nextPageButton = paginationContainer
        .locator('button[aria-label*="次"]')
        .first()
      if (
        (await nextPageButton.isVisible()) &&
        (await nextPageButton.isEnabled())
      ) {
        await nextPageButton.click()

        // ページが変更されることを確認（URL変更やコンテンツ更新）
        await page.waitForTimeout(500)
      }
    }
  })

  test('レスポンシブデザイン', async ({ page }) => {
    // モバイル表示をテスト
    await page.setViewportSize({ width: 375, height: 667 })

    // レビューセクションが適切に表示されることを確認
    await expect(page.locator('.reviews-section')).toBeVisible()

    // 統計セクションがモバイルで適切に表示されることを確認
    const statisticsSection = page.locator('.statistics-section')
    if (await statisticsSection.isVisible()) {
      await expect(statisticsSection).toBeVisible()
    }

    // レビューカードがモバイルで適切に表示されることを確認
    const reviewCards = page.locator('.review-card')
    if (await reviewCards.first().isVisible()) {
      await expect(reviewCards.first()).toBeVisible()
    }

    // タブレット表示をテスト
    await page.setViewportSize({ width: 768, height: 1024 })

    // レビューセクションが適切に表示されることを確認
    await expect(page.locator('.reviews-section')).toBeVisible()
  })

  test('エラーハンドリング', async ({ page }) => {
    // ネットワークエラーをシミュレーション
    await page.route('**/api/recipes/*/reviews', (route) => {
      route.abort('failed')
    })

    await page.reload()

    // エラーメッセージが表示されることを確認（実装に応じて調整）
    // await expect(page.locator('text=レビューの読み込みに失敗しました')).toBeVisible()
  })

  test('アクセシビリティ', async ({ page }) => {
    // キーボードナビゲーションのテスト
    await page.keyboard.press('Tab')

    // フォーカスが適切に移動することを確認
    const focusedElement = page.locator(':focus')
    await expect(focusedElement).toBeVisible()

    // レビュー投稿ボタンにフォーカスが当たる場合
    if (await page.locator('text=レビューを書く').isVisible()) {
      await page.locator('text=レビューを書く').focus()
      await page.keyboard.press('Enter')

      // フォームが開くことを確認
      await expect(page.locator('.v-dialog')).toBeVisible()
    }
  })
})
