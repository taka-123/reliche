# AIレシピ生成機能 API設計書

## Gemini API 連携仕様

### エンドポイント
- **URL**: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite:generateContent`
- **Method**: POST
- **認証**: Bearer Token (API Key)

### リクエスト形式

#### 基本レシピ生成
```json
{
  "contents": [{
    "parts": [{
      "text": "日本の家庭料理のレシピを1つ作成してください。以下のJSON形式で出力してください..."
    }]
  }],
  "generationConfig": {
    "temperature": 0.7,
    "topK": 40,
    "topP": 0.9,
    "maxOutputTokens": 1000
  }
}
```

#### 食材指定レシピ生成
```json
{
  "contents": [{
    "parts": [{
      "text": "以下の食材を使った日本の家庭料理レシピを作成してください: 鶏肉, キャベツ..."
    }]
  }]
}
```

### レスポンス形式

```json
{
  "candidates": [{
    "content": {
      "parts": [{
        "text": "{\n  \"title\": \"鶏肉とキャベツの炒め物\",\n  \"cooking_time\": 20,\n  ..."
      }]
    }
  }]
}
```

## プロンプト設計

### 基本プロンプトテンプレート

```text
日本の家庭料理のレシピを1つ作成してください。

以下のJSON形式で正確に出力してください：

{
  "title": "レシピ名",
  "cooking_time": 調理時間（分、数値のみ）,
  "servings": 人数（数値のみ）,
  "calories": カロリー（kcal、数値のみ）,
  "tags": ["タグ1", "タグ2"],
  "category": "料理カテゴリ",
  "ingredients": [
    {"name": "食材名", "amount": "分量"}
  ],
  "instructions": [
    "手順1",
    "手順2"
  ]
}

制約条件：
- 調理時間は5〜60分
- 人数は1〜6人
- カロリーは100〜800kcal/人
- 材料は2〜15個
- 手順は3〜10個
- 分量は具体的に記載（「適量」「お好みで」禁止）
- 実際に作れる現実的なレシピ
- 日本で入手可能な食材のみ
```

### カテゴリ別プロンプト

#### 和食
```text
{base_prompt}

カテゴリ: 和食
使用する調味料: 醤油、味噌、みりん、酒、だし等の和風調味料を中心に
```

#### 洋食
```text
{base_prompt}

カテゴリ: 洋食
使用する調味料: 塩、こしょう、オリーブオイル、バター、チーズ等の洋風調味料を中心に
```

#### 中華
```text
{base_prompt}

カテゴリ: 中華
使用する調味料: 醤油、オイスターソース、ごま油、豆板醤等の中華調味料を中心に
```

### 食材指定プロンプト

```text
{base_prompt}

指定食材: {ingredients}
上記の食材を必ず使用してレシピを作成してください。
足りない食材は一般的な家庭にある調味料や基本食材で補完してください。
```

### 制約条件プロンプト

```text
{base_prompt}

制約条件:
- 調理時間: {max_time}分以内
- タグ: {tags}
- 難易度: {difficulty}

上記条件を満たすレシピを作成してください。
```

## エラーハンドリング

### APIエラー
- 401: API Key 無効
- 403: 利用制限exceeded
- 429: レート制限
- 500: サーバーエラー

### 生成エラー
- JSON解析失敗
- 必須フィールド不足
- バリデーション失敗
- 不適切な内容

## レスポンス検証

### 必須フィールドチェック
```php
$required_fields = [
    'title', 'cooking_time', 'servings', 
    'calories', 'tags', 'category', 
    'ingredients', 'instructions'
];
```

### 妥当性チェック
```php
// 調理時間: 5-120分
$cooking_time >= 5 && $cooking_time <= 120

// 人数: 1-6人
$servings >= 1 && $servings <= 6

// カロリー: 50-1500kcal
$calories >= 50 && $calories <= 1500

// 材料数: 2-15個
count($ingredients) >= 2 && count($ingredients) <= 15

// 手順数: 3-15個
count($instructions) >= 3 && count($instructions) <= 15
```

## キャッシュ設計

### キャッシュキー
```php
// 基本レシピ
"ai_recipe_basic_{category}_{index}"

// 食材指定
"ai_recipe_ingredients_" . md5(implode(',', $ingredients))

// 制約条件
"ai_recipe_constraints_" . md5($constraints_json)
```

### TTL設定
- 基本レシピ: 24時間
- 食材指定: 1時間
- 制約条件: 6時間

## パフォーマンス最適化

### バッチ処理
- 複数レシピ同時生成
- 非同期処理対応
- 進捗管理

### 並列処理
- 最大3並列まで
- レート制限考慮
- エラー時の再試行