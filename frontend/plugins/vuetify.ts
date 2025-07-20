// plugins/vuetify.ts
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { md3 } from 'vuetify/blueprints'

export default defineNuxtPlugin((nuxtApp) => {
  const vuetify = createVuetify({
    ssr: true,
    components,
    directives,
    // Material Design 3 を使用
    blueprint: md3,
    // アイコンセット - MDI フォントに変更
    icons: {
      defaultSet: 'mdi',
    },
    // カスタムテーマ
    theme: {
      defaultTheme: 'light',
      themes: {
        light: {
          dark: false,
          colors: {
            primary: '#2e7d32', // 料理アプリらしいダークグリーン
            'on-primary': '#ffffff', // primary色の上の文字色（白）
            secondary: '#4caf50', // ライトグリーン
            'on-secondary': '#ffffff', // secondary色の上の文字色（白）
            accent: '#ff8f00', // アクセント（オレンジ系）
            error: '#f44336', // エラー
            warning: '#ff9800', // 警告
            info: '#1b5e20', // 情報（深緑）
            success: '#4caf50', // 成功
            'on-success': '#ffffff', // success色の上の文字色（白）
            background: '#f1f8e9', // 背景色（薄緑）
          },
        },
        dark: {
          dark: true,
          colors: {
            primary: '#4caf50',
            secondary: '#66bb6a',
            accent: '#ffb74d',
            error: '#f44336',
            warning: '#ff9800',
            info: '#2e7d32',
            success: '#4caf50',
            background: '#1b5e20',
          },
        },
      },
    },
    // デフォルトプロパティ
    defaults: {
      VCard: {
        elevation: 2,
        rounded: 'lg',
      },
      VBtn: {
        rounded: 'lg',
        elevation: 2,
        fontWeight: 'medium',
      },
      VAppBar: {
        color: 'primary',
      },
      VAlert: {
        borderRadius: 'lg',
      },
      VTextField: {
        variant: 'outlined',
        density: 'comfortable',
        rounded: 'lg',
      },
      VSelect: {
        variant: 'outlined',
        density: 'comfortable',
        rounded: 'lg',
      },
    },
  })

  nuxtApp.vueApp.use(vuetify)
})
