# AI レシピ生成機能 要件定義書

## 概要

外部 API のコスト問題を解決し、Gemini AI を活用した独自レシピ生成機能を実装します。これにより無限拡張可能で高品質なオリジナルレシピデータベースを構築します。

## 背景・課題

- **楽天レシピ API**: 商用利用禁止、データ古い（2017 年）
- **海外 API**: 月額数万円、日本語レシピ少ない
- **AI レシピ生成**: コスト効率良、著作権クリア、無限拡張可能

## 技術仕様

### 使用技術

- **AI モデル**: Google Gemini 2.5 Flash-Lite
- **API**: Google AI Studio API
- **コスト**: $0.10/$0.40 per 1M tokens
- **無料枠**: 500 リクエスト/日、250,000 トークン/分

### データベース設計

#### recipes テーブル拡張

```sql
ALTER TABLE recipes ADD COLUMN servings INTEGER DEFAULT 2;
ALTER TABLE recipes ADD COLUMN calories INTEGER;
ALTER TABLE recipes ADD COLUMN tags JSON;
ALTER TABLE recipes ADD COLUMN category VARCHAR(255);
ALTER TABLE recipes ADD COLUMN source VARCHAR(255) DEFAULT 'ai_generated';
```

#### 生成レシピ構造

```json
{
  "title": "鶏胸肉とキャベツの味噌マヨ炒め",
  "cooking_time": 15,
  "servings": 2,
  "calories": 280,
  "tags": ["時短", "節約"],
  "category": "和食",
  "source": "ai_generated",
  "ingredients": [
    { "name": "鶏胸肉", "amount": "200g" },
    { "name": "キャベツ", "amount": "1/4個" }
  ],
  "instructions": [
    "鶏胸肉を一口大に切り、塩コショウで下味をつける",
    "フライパンで鶏肉を炒め、色が変わったらキャベツを加える"
  ]
}
```

## 実装方針

### ハイブリッド生成システム

1. **事前生成**: 基本レシピ 50 種（カテゴリ別）
2. **リアルタイム生成**: ユーザー食材指定時
3. **キャッシュ活用**: 同一条件は再利用

### 品質保証

- 材料の分量明確化（「適量」「お好みで」禁止）
- 調理時間具体的記載
- 火加減・温度詳細指定
- 失敗ポイントと注意事項記載

## コマンド仕様

### 基本レシピ生成

```bash
php artisan recipe:generate --count=50 --category=和食
```

### 食材指定レシピ生成

```bash
php artisan recipe:generate --ingredients="鶏肉,キャベツ" --count=10
```

### 制約条件付きレシピ生成

```bash
php artisan recipe:generate --tags="時短,節約" --max-time=20
```

## 成功指標

- **生成成功率**: 95%以上
- **レシピ妥当性**: 人的検証で 90%以上
- **生成速度**: 1 レシピあたり 10 秒以内
- **重複率**: 5%以下
- **コスト**: 月 1000 レシピで$5 以下

## セキュリティ・プライバシー

- **商用利用**: 有料版使用でデータ保護
- **API Key**: 環境変数で管理
- **レート制限**: 適切な制御実装

## 運用計画

### Phase 1: MVP 実装（2 週間）

- Gemini API 連携実装
- 基本レシピ生成コマンド作成
- 50 種類の基本レシピ生成・投入
- 品質検証機能実装

### Phase 2: ユーザー機能拡張（4 週間）

#### 2.1 API・UI 機能

- 食材指定レシピ生成 API
- フロントエンド統合（レシピ生成 UI）
- 管理画面でのレシピ管理

#### 2.2 栄養・調理法解説システム

- 食材マスタテーブル（栄養情報管理）
- レシピ食材の栄養解説機能
- トグル表示対応（表示/非表示切り替え）

#### 2.3 タグ・検索・関連機能

- 自動タグ生成システム
- 関連レシピ推薦機能
- 多角的検索（食材・時間・カロリー・タグ）
- Elasticsearch 導入検討

#### 2.4 画像生成機能

- MCP 画像生成機能統合
- レシピ画像自動生成
- フォールバック画像システム

### Phase 3: 品質管理・コミュニティ機能（6 週間）

#### 3.1 レシピ評価システム

- レビュー・評価テーブル設計
- 5 段階評価（味・難易度・手順明確性）
- 評価コメント機能

#### 3.2 品質管理自動化

- 評価 3 未満レシピの自動削除バッチ
- 代替レシピ自動生成・補完
- 週次/月次品質チェック機能

#### 3.3 ユーザー投稿機能

- レシピ画像・動画投稿
- ユーザーレビュー画像
- 投稿コンテンツ管理・承認システム

#### 3.4 AI 学習・最適化

- ユーザー好み学習機能
- 季節食材対応
- レシピ改善 AI フィードバック

## データベース拡張設計

## データベース設計標準化

### 監査カラム統一仕様

全カスタムテーブルに以下の監査カラムを標準装備：

```sql
-- 監査カラム（全テーブル共通）
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
created_by BIGINT REFERENCES users(id) NULL,
updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
updated_by BIGINT REFERENCES users(id) NULL,
deleted_at TIMESTAMP NULL,
deleted_by BIGINT REFERENCES users(id) NULL,
```

### 既存テーブル拡張

```sql
-- recipesテーブル監査カラム追加
ALTER TABLE recipes ADD COLUMN deleted_at TIMESTAMP NULL;
ALTER TABLE recipes ADD COLUMN created_by BIGINT REFERENCES users(id);
ALTER TABLE recipes ADD COLUMN updated_by BIGINT REFERENCES users(id);
ALTER TABLE recipes ADD COLUMN deleted_by BIGINT REFERENCES users(id);

-- ingredientsテーブル監査カラム追加
ALTER TABLE ingredients ADD COLUMN deleted_at TIMESTAMP NULL;
ALTER TABLE ingredients ADD COLUMN created_by BIGINT REFERENCES users(id);
ALTER TABLE ingredients ADD COLUMN updated_by BIGINT REFERENCES users(id);
ALTER TABLE ingredients ADD COLUMN deleted_by BIGINT REFERENCES users(id);

-- recipe_ingredientsテーブル監査カラム追加
ALTER TABLE recipe_ingredients ADD COLUMN deleted_at TIMESTAMP NULL;
ALTER TABLE recipe_ingredients ADD COLUMN created_by BIGINT REFERENCES users(id);
ALTER TABLE recipe_ingredients ADD COLUMN updated_by BIGINT REFERENCES users(id);
ALTER TABLE recipe_ingredients ADD COLUMN deleted_by BIGINT REFERENCES users(id);
```

### Phase 2 対応テーブル

```sql
-- 食材栄養情報マスタ
CREATE TABLE ingredients_nutrition (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ingredient_name VARCHAR(255) UNIQUE NOT NULL,
    nutrition_facts JSON COMMENT 'カロリー、ビタミン、ミネラル等',
    health_benefits JSON COMMENT '効能情報',
    cooking_tips JSON COMMENT '調理法による栄養変化',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT REFERENCES users(id),
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT REFERENCES users(id),
    INDEX idx_ingredient_name (ingredient_name),
    INDEX idx_deleted_at (deleted_at)
);

-- レシピ食材テーブル拡張
ALTER TABLE recipe_ingredients ADD COLUMN nutrition_notes TEXT COMMENT 'この食材特有の栄養メモ';
ALTER TABLE recipe_ingredients ADD COLUMN cooking_method_tips TEXT COMMENT 'この調理法での栄養ポイント';
```

### Phase 3 対応テーブル

```sql
-- レビューシステム
CREATE TABLE recipe_reviews (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    recipe_id BIGINT NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
    taste_score INTEGER CHECK (taste_score >= 1 AND taste_score <= 5),
    difficulty_score INTEGER CHECK (difficulty_score >= 1 AND difficulty_score <= 5),
    instruction_clarity INTEGER CHECK (instruction_clarity >= 1 AND instruction_clarity <= 5),
    comment TEXT,
    review_images JSON COMMENT '投稿画像パス配列',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT REFERENCES users(id),
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT REFERENCES users(id),
    INDEX idx_recipe_id (recipe_id),
    INDEX idx_user_id (user_id),
    INDEX idx_rating (rating),
    INDEX idx_deleted_at (deleted_at)
);

-- ユーザー投稿メディア
CREATE TABLE recipe_media (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    recipe_id BIGINT NOT NULL REFERENCES recipes(id) ON DELETE CASCADE,
    user_id BIGINT REFERENCES users(id) ON DELETE SET NULL,
    media_type ENUM('image', 'video') NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255),
    file_size INTEGER COMMENT 'ファイルサイズ（bytes）',
    mime_type VARCHAR(100),
    metadata JSON COMMENT '画像サイズ、形式等の追加情報',
    description TEXT,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by BIGINT REFERENCES users(id),
    approved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by BIGINT REFERENCES users(id),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by BIGINT REFERENCES users(id),
    deleted_at TIMESTAMP NULL,
    deleted_by BIGINT REFERENCES users(id),
    INDEX idx_recipe_id (recipe_id),
    INDEX idx_user_id (user_id),
    INDEX idx_media_type (media_type),
    INDEX idx_is_approved (is_approved),
    INDEX idx_deleted_at (deleted_at)
);

-- 検索最適化インデックス
CREATE TABLE recipe_search_index (
    recipe_id BIGINT PRIMARY KEY REFERENCES recipes(id) ON DELETE CASCADE,
    searchable_text TEXT,
    tags_text TEXT,
    ingredients_text TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT(searchable_text, tags_text, ingredients_text)
);
```

### インデックス最適化

```sql
-- recipesテーブル最適化
ALTER TABLE recipes ADD INDEX idx_category (category);
ALTER TABLE recipes ADD INDEX idx_cooking_time (cooking_time);
ALTER TABLE recipes ADD INDEX idx_calories (calories);
ALTER TABLE recipes ADD INDEX idx_source (source);
ALTER TABLE recipes ADD INDEX idx_deleted_at (deleted_at);

-- recipe_ingredientsテーブル最適化
ALTER TABLE recipe_ingredients ADD INDEX idx_recipe_id (recipe_id);
ALTER TABLE recipe_ingredients ADD INDEX idx_ingredient_id (ingredient_id);
ALTER TABLE recipe_ingredients ADD INDEX idx_deleted_at (deleted_at);

-- ingredientsテーブル最適化
ALTER TABLE ingredients ADD INDEX idx_name (name);
ALTER TABLE ingredients ADD INDEX idx_deleted_at (deleted_at);
```
