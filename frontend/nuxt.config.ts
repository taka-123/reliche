// https://nuxt.com/docs/api/configuration/nuxt-config
import { defineNuxtConfig } from 'nuxt/config'
import vuetify, { transformAssetUrls } from 'vite-plugin-vuetify'

export default defineNuxtConfig({
  modules: [
    '@pinia/nuxt',
    '@nuxt/eslint',
    (_options, nuxt) => {
      nuxt.hooks.hook('vite:extendConfig', (config) => {
        config.plugins.push(vuetify({ autoImport: true }))
      })
    },
  ],
  devtools: { enabled: true },
  css: [
    'vuetify/lib/styles/main.sass',
    '@mdi/font/css/materialdesignicons.min.css',
    '~/assets/css/main.css',
  ],
  runtimeConfig: {
    public: {
      // クライアントサイド（ブラウザ）用API URL
      apiBase: process.env.BROWSER_API_BASE_URL || 'http://localhost:8000/api',
      // サーバーサイド（Dockerコンテナ内）用API URL
      serverApiBase:
        process.env.SERVER_API_BASE_URL || 'http://laravel.test/api',
      appEnv: (() => {
        const env = process.env.NODE_ENV || 'development'
        // test, stagingなどの環境も適切に処理
        if (env === 'development' || env === 'test') {
          return 'development' as const
        }
        return 'production' as const
      })(),
    },
  },
  build: {
    transpile: ['vuetify'],
  },
  compatibilityDate: '2024-11-01',
  vite: {
    vue: {
      template: {
        transformAssetUrls,
      },
    },
    optimizeDeps: {
      include: ['vue', 'vue-router', 'pinia', '@vueuse/core', '@vueuse/head'],
      exclude: ['vue-demi'],
    },
  },
  typescript: {
    strict: false,
    typeCheck: false,
    shim: true,
  },
})
