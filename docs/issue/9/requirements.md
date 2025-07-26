# AIレシピ生成機能 要件定義書

## 概要

外部APIのコスト問題を解決し、Gemini AIを活用した独自レシピ生成機能を実装します。これにより無限拡張可能で高品質なオリジナルレシピデータベースを構築します。

## 背景・課題

- **楽天レシピAPI**: 商用利用禁止、データ古い（2017年）
- **海外API**: 月額数万円、日本語レシピ少ない
- **AIレシピ生成**: コスト効率良、著作権クリア、無限拡張可能

## 技術仕様

### 使用技術

- **AIモデル**: Google Gemini 2.5 Flash-Lite
- **API**: Google AI Studio API
- **コスト**: $0.10/$0.40 per 1M tokens
- **無料枠**: 500リクエスト/日、250,000トークン/分

### データベース設計

#### recipesテーブル拡張

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
    {"name": "鶏胸肉", "amount": "200g"},
    {"name": "キャベツ", "amount": "1/4個"}
  ],
  "instructions": [
    "鶏胸肉を一口大に切り、塩コショウで下味をつける",
    "フライパンで鶏肉を炒め、色が変わったらキャベツを加える"
  ]
}
```

## 実装方針

### ハイブリッド生成システム

1. **事前生成**: 基本レシピ50種（カテゴリ別）
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
- **レシピ妥当性**: 人的検証で90%以上
- **生成速度**: 1レシピあたり10秒以内
- **重複率**: 5%以下
- **コスト**: 月1000レシピで$5以下

## セキュリティ・プライバシー

- **商用利用**: 有料版使用でデータ保護
- **API Key**: 環境変数で管理
- **レート制限**: 適切な制御実装

## 運用計画

### Phase 1: MVP実装（2週間）
- Gemini API連携実装
- 基本レシピ生成コマンド作成
- 50種類の基本レシピ生成・投入
- 品質検証機能実装

### Phase 2: 機能拡張（4週間）
- 食材指定レシピ生成
- 制約条件付き生成
- 管理画面でのレシピ管理

### Phase 3: 最適化（6週間）
- ユーザー好み学習機能
- 季節食材対応
- レシピ改善AIフィードバック