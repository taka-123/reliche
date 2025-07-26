# AIレシピ生成機能 TODO リスト

## 🚀 実装タスク

### Phase 1: 基盤構築

- [x] ブランチ作成 (feat/9-ai-recipe-generation)
- [x] ドキュメント作成 (requirements.md, TODO.md)
- [x] データベースマイグレーション作成
- [x] Recipeモデル拡張
- [x] 環境設定(.env.example更新)

### Phase 2: サービス層

- [x] AIRecipeGeneratorService作成
  - [x] Gemini API連携
  - [x] プロンプトエンジニアリング
  - [x] レスポンス解析・バリデーション
  - [x] エラーハンドリング
- [x] 品質検証システム
  - [x] 材料妥当性チェック
  - [x] 調理時間検証
  - [x] 重複チェック

### Phase 3: コマンド実装

- [x] GenerateRecipesCommand作成
  - [x] 基本生成機能
  - [x] オプション処理 (--count, --category, --ingredients)
  - [x] 進捗表示
  - [x] ログ出力
- [x] AIRecipeSeeder作成
  - [x] カテゴリ別レシピ生成
  - [x] バランス配分

### Phase 4: API・統合

- [x] 既存Recipe API拡張
- [x] お気に入り機能連携確認
- [x] キャッシュシステム構築

### Phase 5: テスト・検証

- [x] ユニットテスト
  - [x] AIRecipeGeneratorServiceテスト
  - [x] GenerateRecipesCommandテスト
- [x] 統合テスト
  - [x] API連携テスト
  - [x] 既存機能互換性テスト
- [x] 品質検証
  - [x] 50レシピ生成テスト
  - [x] 妥当性検証

## 📋 詳細タスク

### データベース設計
```sql
-- recipesテーブル拡張
ALTER TABLE recipes ADD COLUMN servings INTEGER DEFAULT 2;
ALTER TABLE recipes ADD COLUMN calories INTEGER;
ALTER TABLE recipes ADD COLUMN tags JSON;
ALTER TABLE recipes ADD COLUMN category VARCHAR(255);
ALTER TABLE recipes ADD COLUMN source VARCHAR(255) DEFAULT 'ai_generated';
```

### 必要なファイル

#### バックエンド
- `database/migrations/xxxx_add_ai_fields_to_recipes_table.php`
- `app/Models/Recipe.php` (拡張)
- `app/Services/AIRecipeGeneratorService.php`
- `app/Console/Commands/GenerateRecipesCommand.php`
- `database/seeders/AIRecipeSeeder.php`

#### テスト
- `tests/Unit/Services/AIRecipeGeneratorServiceTest.php`
- `tests/Feature/Commands/GenerateRecipesCommandTest.php`

#### 設定
- `.env.example` (GEMINI_API_KEY追加)

## 🎯 マイルストーン

### Milestone 1: 基盤完成
- データベース設計完了
- サービス層基本実装完了
- 1レシピ生成成功

### Milestone 2: コマンド完成
- GenerateRecipesCommand動作
- 10レシピ一括生成成功
- 品質検証通過

### Milestone 3: 統合完成
- 50レシピ生成・投入完了
- 既存システム連携確認
- テスト完了

## ⚠️ 注意事項

- API Key の適切な管理
- レート制限の考慮
- エラーハンドリングの充実
- 生成レシピの品質検証
- 既存機能への影響チェック

## 🔍 品質チェックリスト

- [x] 材料分量が具体的（「適量」禁止）
- [x] 調理時間が現実的（5分〜120分）
- [x] カロリーが妥当（100〜1000kcal/人）
- [x] 手順が論理的で実行可能
- [x] 日本語が自然
- [x] アレルギー情報考慮

## ✅ 実装完了状況

**Phase 1-5の全てのタスクが完了しました！**

AIレシピ生成機能は完全に実装され、以下のコマンドで利用可能です：

```bash
# AIレシピ生成（APIキー設定必要）
docker exec reliche-laravel.test-1 php artisan recipe:generate --count=5 --category=和食

# AIレシピシーダー実行
docker exec reliche-laravel.test-1 php artisan db:seed --class=AIRecipeSeeder
```