# Phase 2 プロンプト最適化タスク

## 背景

Phase 1 では既存 DB 構造に合わせたシンプルなプロンプトを使用したが、Phase 2 で DB 拡張が行われる際に、新しいテーブル・カラムに対応した高品質プロンプトが必要。

## 課題

- Phase 1 では途中で JSON レスポンスを DB 構造に合わせて変換する複雑な処理が発生
- **この複雑な変換処理を避けて、直接 DB 構造に合致する JSON を生成したい**

## Phase 2 完成時の DB 構造

### recipes テーブル（既存拡張済み）

```sql
recipes:
- id, name, cooking_time, instructions
- servings, calories, tags (JSON), category, source
- created_at, created_by, updated_at, updated_by, deleted_at, deleted_by
```

### 新規テーブル（Phase 2 で追加予定）

#### ingredients_nutrition（食材栄養マスタ）

```sql
- id, ingredient_name (UNIQUE)
- nutrition_facts (JSON): カロリー、ビタミン、ミネラル等
- health_benefits (JSON): 効能情報
- cooking_tips (JSON): 調理法による栄養変化
- 監査カラム一式
```

#### recipe_ingredients 拡張

```sql
既存: recipe_id, ingredient_id, quantity
追加:
- nutrition_notes (TEXT): この食材特有の栄養メモ
- cooking_method_tips (TEXT): この調理法での栄養ポイント
- 監査カラム一式
```

## AI モデル使い分け戦略

### 事前生成（バッチ）: **Gemini 2.5 Pro**

- 最高品質、複雑な推論
- 栄養解説、季節食材組み合わせ
- 時間制約なし

### リアルタイム生成: **Gemini 2.5 Flash-Lite**

- 高速レスポンス重視
- ユーザー待機時間最小化

### 開発・テスト: **Flash-Lite**

- 無料枠節約

## Phase 2 プロンプト最適化要件

### 1. 直接 DB 対応 JSON 生成

```json
{
  "recipe": {
    "title": "レシピ名",
    "cooking_time": 30,
    "servings": 4,
    "calories": 500,
    "tags": ["時短", "栄養豊富", "簡単"],
    "category": "和食",
    "instructions": ["手順1", "手順2"]
  },
  "ingredients": [
    {
      "name": "鶏胸肉",
      "amount": "200g",
      "nutrition_notes": "高タンパク質でダイエット向き。皮を除くとよりヘルシー。",
      "cooking_method_tips": "パサつき防止のため、塩水につけて下処理することで柔らかく仕上がる。"
    }
  ],
  "nutrition_data": [
    {
      "ingredient_name": "鶏胸肉",
      "nutrition_facts": {
        "calories_per_100g": 108,
        "protein": 22.3,
        "fat": 1.5,
        "carbs": 0,
        "vitamins": { "B6": "豊富", "ナイアシン": "豊富" }
      },
      "health_benefits": {
        "muscle_building": "高タンパクで筋肉量維持に効果的",
        "diet": "低脂肪でダイエット向き",
        "fatigue": "ビタミンB群で疲労回復"
      },
      "cooking_tips": {
        "boiling": "茹でると最も低カロリー",
        "grilling": "焼くと香ばしく仕上がるが油分注意",
        "steaming": "蒸すと栄養素流出最小"
      }
    }
  ]
}
```

### 2. Pro 用高品質要件

- **栄養解説の専門性**: 管理栄養士レベルの知識
- **調理科学の根拠**: なぜその調理法が良いかの科学的説明
- **季節・体調考慮**: 時期や健康状態に応じた提案
- **食材相性解説**: 組み合わせによる相乗効果

### 3. Flash-Lite 用簡潔要件

- 基本レシピ情報のみ
- nutrition_notes, cooking_method_tips は簡潔に
- nutrition_data は省略可能

## 実装時の注意点

1. **JSON 構造を DB 構造に完全一致させる**
2. **変換処理を最小限に抑える**
3. **Pro/Flash-Lite でプロンプトを使い分ける**
4. **栄養データは ingredients_nutrition テーブルに自動挿入**
5. **重複チェック機能（同じ食材の栄養データ重複回避）**

## タスク

Phase 2 実装時に以下を実行：

1. Phase 2 DB マイグレーション完了後
2. 上記 JSON 構造に対応したプロンプト作成
3. Pro 用/Flash-Lite 用の 2 パターン作成
4. AIRecipeGeneratorService の parseRecipeResponse() メソッド更新
5. 栄養データ自動挿入ロジック追加
6. テストケース更新

## 期待効果

- **複雑な変換処理ゼロ**
- **高品質な栄養解説自動生成**
- **Pro/Flash-Lite 使い分けによるコスト最適化**
- **Phase 2 機能のフル活用**
