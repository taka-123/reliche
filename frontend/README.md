# Laravel + Nuxt + PostgreSQL テンプレート フロントエンド

このディレクトリにはLaravel + Nuxt + PostgreSQL テンプレートのフロントエンド（Nuxt.js 3.16）が含まれています。

## フロントエンド技術スタック

- **フレームワーク**: Nuxt.js 3.16
- **UIフレームワーク**: Vuetify 3.4
- **状態管理**: Pinia
- **HTTP通信**: Axios
- **コード品質**: ESLint + Prettier
- **テスト**: Vitest + Vue Test Utils

## セットアップ

依存関係をインストールします：

```bash
# npm
npm install
```

## 開発サーバー

開発サーバーを起動します（http://localhost:3000）：

```bash
# npm
npm run dev
```

## コマンド一覧

```bash
# 開発サーバー起動
npm run dev

# 本番用ビルド
npm run build

# 本番ビルドのプレビュー
npm run preview

# コード品質チェック
npm run lint

# コード品質チェックと自動修正
npm run lint:fix

# テスト実行
npm test

# テスト（ウォッチモード）
npm run test:watch

# テストカバレッジ
npm run test:coverage
```

## ディレクトリ構造

```
frontend/
├── composables/      # 再利用可能なVue Composables
│   ├── useApi.ts     # API通信用コンポーザブル
│   └── useAuth.ts    # 認証用コンポーザブル
├── docker/           # Docker設定ファイル
│   └── nginx/        # Nginx設定
├── layouts/          # レイアウトコンポーネント
├── pages/            # ページコンポーネント（ルーティング）
├── plugins/          # Nuxtプラグイン
│   ├── axios.ts      # Axios設定
│   ├── pinia.ts      # Pinia設定
│   └── vuetify.ts    # Vuetify設定
├── public/           # 公開ファイル
├── server/           # サーバーサイド設定
├── stores/           # Piniaストア
├── test/             # テストファイル
├── types/            # TypeScript型定義
├── package.json      # npm依存関係
├── nuxt.config.ts    # Nuxt設定
├── tsconfig.json     # TypeScript設定
├── Dockerfile.fly    # Fly.io用Dockerfile
├── fly.toml         # Fly.io設定
└── vitest.config.ts  # Vitest設定
```

## 詳細情報

詳細については、以下を参照してください：

- [Nuxt 3 ドキュメント](https://nuxt.com/docs/getting-started/introduction)
- [Vuetify 3 ドキュメント](https://vuetifyjs.com/en/introduction/why-vuetify/)
- [プロジェクト開発環境ガイド](../DEVELOPMENT.md)
