// plugins/vuetify.ts
import { createVuetify } from 'vuetify'
import { md3 } from 'vuetify/blueprints'
import * as components from 'vuetify/components'
import * as directives from 'vuetify/directives'
import { defineNuxtPlugin } from '#app'

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
            'primary': '#2e7d32',
            'on-primary': '#ffffff',
            'primary-container': '#c8e6c9',
            'on-primary-container': '#1b5e20',
            'secondary': '#4caf50',
            'on-secondary': '#ffffff',
            'secondary-container': '#a5d6a7',
            'on-secondary-container': '#2e7d32',
            'tertiary': '#66bb6a',
            'on-tertiary': '#ffffff',
            'tertiary-container': '#e8f5e8',
            'on-tertiary-container': '#1b5e20',
            'error': '#f44336',
            'on-error': '#ffffff',
            'error-container': '#ffebee',
            'on-error-container': '#b71c1c',
            'background': '#f1f8e9',
            'on-background': '#1a1c18',
            'surface': '#ffffff',
            'on-surface': '#1a1c18',
            'surface-variant': '#f5f5f5',
            'on-surface-variant': '#424242',
            'outline': '#757575',
            'outline-variant': '#c4c7c5',
            'shadow': '#000000',
            'scrim': '#000000',
            'inverse-surface': '#2f312e',
            'inverse-on-surface': '#f0f1ec',
            'inverse-primary': '#a0cfa4',
          },
        },
        dark: {
          dark: true,
          colors: {
            'primary': '#4caf50',
            'on-primary': '#1b5e20',
            'primary-container': '#2e7d32',
            'on-primary-container': '#c8e6c9',
            'secondary': '#66bb6a',
            'on-secondary': '#1b5e20',
            'secondary-container': '#388e3c',
            'on-secondary-container': '#c8e6c9',
            'tertiary': '#81c784',
            'on-tertiary': '#1b5e20',
            'tertiary-container': '#2e7d32',
            'on-tertiary-container': '#e8f5e8',
            'error': '#f44336',
            'on-error': '#ffffff',
            'error-container': '#d32f2f',
            'on-error-container': '#ffcdd2',
            'background': '#0d1b0f',
            'on-background': '#e8f5e8',
            'surface': '#1a1c18',
            'on-surface': '#e8f5e8',
            'surface-variant': '#424242',
            'on-surface-variant': '#c4c7c5',
            'outline': '#757575',
            'outline-variant': '#424242',
            'shadow': '#000000',
            'scrim': '#000000',
            'inverse-surface': '#e8f5e8',
            'inverse-on-surface': '#1a1c18',
            'inverse-primary': '#2e7d32',
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

  // Material Design 3 color inheritance fix
  if (import.meta.client) {
    const style = document.createElement('style')
    style.textContent = `
      /* MD3準拠: v-btn__contentがon-primary色を正しく継承 */
      .v-btn.bg-primary .v-btn__content,
      .v-btn.bg-primary .v-btn__content * {
        color: rgb(var(--v-theme-on-primary));
      }

      /* MD3準拠: その他のプライマリ色ボタンも同様 */
      .v-btn[style*="background-color: rgb(var(--v-theme-primary))"] .v-btn__content,
      .v-btn[style*="background-color: rgb(var(--v-theme-primary))"] .v-btn__content * {
        color: rgb(var(--v-theme-on-primary));
      }
    `
    document.head.appendChild(style)
  }

  nuxtApp.vueApp.use(vuetify)
})
