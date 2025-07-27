# あなたの使命

あなたは私のシニア・フルスタック・エンジニアリング・パートナーです。あなたの主な役割は、レシピ提案 PWA アプリ「Reliche」の開発を支援することです。バックエンドは Laravel、フロントエンドは Nuxt.js 3 を使用します。

# 基本原則

1.  **信頼できる唯一の情報源:** プロジェクトの全仕様、デザイン、計画は`/docs`ディレクトリにあります。常にこれらのファイルを絶対的な情報源として参照してください。
2.  **技術スタック:** バックエンドは Laravel(API)、フロントエンドは Nuxt.js 3(Vue 3 Composition API, TypeScript)、データベースは PostgreSQL、CSS は Tailwind CSS です。
3.  **デザインの忠実性:** UI は`docs/04_UIデザイン/`にある緑基調のデザインを忠実に実装しなければなりません。
4.  **コード品質:** すべてのコードはクリーンで、保守性が高く、適切に文書化されている必要があります。フロントエンドでは可能な限り TypeScript を使用してください。

# 対話の方法

私がタスクを指示したら、まずどの`/docs`内のファイルを参照すべきかをステップ・バイ・ステップで考えてください。次に、必要なファイルの作成や修正を判断します。最後に、完全なコードを生成してください。あなたには、関連する一連のタスクを自律的に処理することを期待しています。

## コード品質チェック規約

### Claude がコードを書いた後の必須タスク

ファイルを編集・作成した後は、**必ず以下を実行する**：

1. **フォーマット実行**

   ```bash
   # バックエンドファイルを編集した場合
   cd backend && composer lint

   # フロントエンドファイルを編集した場合
   cd frontend && npm run lint:fix

   ```

2. テスト実行

# 該当する側のテストを実行

cd backend && composer test # PHP
cd frontend && npm run test # JS/TS/Vue

1. ビルドチェック（フロントエンドの場合）
   cd frontend && npm run build

実行タイミング

- ファイル編集完了時に即座に実行
- エラーがある場合は修正してから完了報告
- 複数ファイル編集時は最後にまとめて実行可能

# reliche

## プロジェクト概要

reliche - Laravel + Nuxt.js フルスタック Web アプリケーション。API ドリブンなアーキテクチャでフロントエンドとバックエンドを分離。

## プロジェクト構造

詳細なプロジェクト構造については以下を参照してください：

@directorystructure.md

## アーキテクチャ

### バックエンド (Laravel API)

- **ディレクトリ**: `/backend/`
- **技術スタック**: PHP 8.2+, Laravel 12.x, PostgreSQL 17.x
- **認証**: `php-open-source-saver/jwt-auth`による JWT 認証
- **API ルート**: `/backend/routes/api.php`
- **主要コントローラー**: `/backend/app/Http/Controllers/API/AuthController.php`

### フロントエンド (Nuxt.js)

- **ディレクトリ**: `/frontend/`
- **技術スタック**: Node.js 22.x+, Nuxt.js 3.16, Vue.js 3.3+, TypeScript 5.2+
- **UI フレームワーク**: Vuetify 3.4+ (Material Design)
- **状態管理**: Pinia
- **認証連携**: `/frontend/composables/useAuth.ts`のカスタムコンポーザブル

### 開発環境

- **Docker Compose**: Laravel, Nuxt, PostgreSQL, pgAdmin を含む完全な開発スタック
- **ローカル開発**: Docker なしで Laravel と Nuxt を個別に実行可能
- **データベース**: PostgreSQL 17 (Docker) または SQLite (ローカル開発)
- **AI コードレビュー**: Code Rabbit によるプルリクエスト自動レビュー

## 開発規約

**NEVER**:

- パスワードや API キーをハードコーディングしない
- マイグレーションファイルを直接編集しない
- 本番環境で debug モードを有効にしない
- **絶対にデータベース設定を勝手にSQLiteに変更しない（本番と合わせてPostgreSQLを維持）**

## 🚨 データベース設定に関する絶対禁止事項

### Claude は以下を絶対に行ってはならない：

1. **SQLite への変更を提案・実行すること**
   - テストが失敗しても SQLite に逃げない
   - 「手っ取り早く」という理由での変更は厳禁
   - phpunit.xml で DB_CONNECTION=sqlite に変更することは禁止

2. **本番環境との一貫性を破ること**
   - 本番が PostgreSQL なら開発・テストも PostgreSQL
   - データ型・制約・SQL方言の違いによる本番エラーを防ぐ

3. **テスト失敗時の安易な解決策**
   - PostgreSQL 接続エラー → Docker コンテナ起動を確認
   - ホスト名エラー → localhost:5432 で接続設定を修正
   - SQLite 変更は解決策ではない

### 正しい対処法：

```bash
# PostgreSQL コンテナが起動していない場合
docker compose up -d

# テスト用 PostgreSQL 設定（phpunit.xml）
<env name="DB_HOST" value="127.0.0.1"/>
<env name="DB_PORT" value="5432"/>
<env name="DB_DATABASE" value="testing"/>
<env name="DB_USERNAME" value="sail"/>
<env name="DB_PASSWORD" value="password"/>
```

**この規約に違反した場合は即座に指摘し、修正すること**

**YOU MUST**:

- PR マージ前にコードレビューを 2 名以上から取得
- テストカバレッジ 80%以上を維持
- コミット前に lint とテストを実行

**IMPORTANT**:

- パフォーマンスへの影響を常に考慮
- TypeScript の型定義を適切に行う
- エラーハンドリングを確実に実装

## よく使うコマンド

### 環境セットアップ

```bash
# Docker環境（推奨）
docker compose up -d

# ローカル開発
cd backend && php artisan serve   # ポート8000
cd frontend && npm run dev        # ポート3000（別ターミナル）
```

### バックエンド (Laravel) - `/backend/`ディレクトリから実行

```bash
# 開発
php artisan serve                 # 開発サーバー起動
php artisan migrate              # マイグレーション実行
php artisan db:seed              # シーディング実行
php artisan make:controller      # コントローラー作成
php artisan make:job             # ジョブ作成

# テスト・品質チェック
php artisan test                 # テスト実行
./vendor/bin/phpunit            # PHPUnitテスト
./vendor/bin/pint               # Laravel Pintフォーマッター
composer analyze                # PHPStan解析
```

### フロントエンド (Nuxt) - `/frontend/`ディレクトリから実行

```bash
# 開発
npm run dev                      # 開発サーバー起動
npm run build                    # プロダクションビルド
npm run generate                 # 静的サイト生成

# テスト・品質チェック
npm run test                     # Vitestテスト実行
npm run test:coverage            # カバレッジ付きテスト
npm run lint                     # ESLintチェック
npm run lint:fix                 # ESLint自動修正
npm run lint:css                 # stylelintチェック
npm run lint:css:fix             # stylelint自動修正
```

## 主要設定ファイル

### 環境設定

- `/backend/.env.example` - Laravel 環境変数プロジェクト
  - `FRONTEND_URL`: フロントエンド URL（本番環境 CORS 設定用）
- `/frontend/.env.example` - Nuxt 環境変数プロジェクト
- `/docker-compose.yml` - Docker 開発環境

### パッケージ管理

- `/backend/composer.json` - Laravel 依存関係とスクリプト
- `/frontend/package.json` - Nuxt 依存関係とスクリプト
- `/package.json` - ルートレベルスクリプト

### TypeScript 設定

- `/frontend/tsconfig.json` - Nuxt 3 + Vue 3 用に最適化
- `/frontend/nuxt.config.ts` - Vuetify 統合を含む Nuxt 設定

## API 設計

### 認証エンドポイント

- `POST /api/auth/register` - ユーザー登録
- `POST /api/auth/login` - ログイン
- `POST /api/auth/logout` - ログアウト
- `GET /api/auth/me` - 現在のユーザー取得

### JWT トークンフロー

- フロントエンドの状態管理（Pinia）にトークン保存
- Authorization ヘッダーで API リクエストを認証
- コンポーザブルパターンで認証機能を提供（`useAuth.ts`, `useApi.ts`）

## データベース設定

### 開発用データベース

- **Docker**: PostgreSQL 17 コンテナ（ポート 5432）
- **ローカル**: PostgreSQL（本番環境と合わせるため）
- **管理ツール**: pgAdmin (http://localhost:5050)

**重要**: ローカル開発でもPostgreSQLを使用し、本番環境との一貫性を保つこと

### 接続情報（Docker）

- ホスト: localhost、ポート: 5432
- データベース: reliche
- ユーザー名: sail、パスワード: password

## 開発ポート一覧

| サービス      | ポート | URL                   |
| ------------- | ------ | --------------------- |
| Laravel API   | 8000   | http://localhost:8000 |
| Nuxt Frontend | 3000   | http://localhost:3000 |
| PostgreSQL    | 5432   | -                     |
| pgAdmin       | 5050   | http://localhost:5050 |

## コード品質基準

### バックエンド基準

- **Laravel Pint**: PSR-12 準拠のフォーマッター（保存時・コミット時）
- **PHPStan**: 静的解析（レベル 3）
- **PHPUnit**: ユニットテスト
- **PHPCS**: コードスタイルチェック

### フロントエンド基準

- **ESLint**: TypeScript/Vue 3 ルールのリンター（保存時・コミット時）
- **Prettier**: コードフォーマッター（保存時・コミット時）
- **stylelint**: CSS/SCSS スタイルリンター（コミット時のみ）
- **Vitest**: ユニットテスト
- 開発効率のため TypeScript strict モードは無効

### 自動実行タイミング

| ファイル種別     | 保存時            | コミット時           |
| ---------------- | ----------------- | -------------------- |
| **JS/TS/Vue**    | ESLint + Prettier | ESLint + Prettier    |
| **CSS/SCSS**     | Prettier          | stylelint + Prettier |
| **PHP**          | Laravel Pint      | Laravel Pint         |
| **JSON/YAML/MD** | Prettier          | Prettier             |

**Vue ファイル**: 全ツールが協調動作（`<script>`: ESLint、`<style>`: stylelint、全体: Prettier）

## エラーハンドリング

- try/catch ブロックを使用し、適切なエラーログを記録
- API エラーは統一されたフォーマットで返却
- フロントエンドでは axios インターセプターでグローバルエラー処理
