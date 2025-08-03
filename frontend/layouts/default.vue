<template>
  <v-app>
    <v-app-bar
      app
      color="primary"
      class="app-header"
    >
      <!-- レスポンシブなタイトル表示 -->
      <v-app-bar-title class="d-flex align-center">
        <NuxtLink
          to="/"
          class="text-decoration-none text-white d-flex align-center"
        >
          <span class="d-none d-sm-block">reliche</span>
          <span class="d-block d-sm-none">R</span>
        </NuxtLink>
      </v-app-bar-title>

      <v-spacer />

      <!-- デスクトップ用ナビゲーション -->
      <div class="d-none d-md-flex">
        <v-btn
          to="/"
          variant="text"
          color="white"
        >
          ホーム
        </v-btn>
        <ClientOnly>
          <template v-if="isAuthenticated">
            <v-btn
              to="/favorites"
              variant="text"
              color="white"
            >
              お気に入り
            </v-btn>
            <v-btn
              variant="text"
              color="white"
              @click="handleLogout"
            >
              ログアウト
            </v-btn>
          </template>
          <template v-else>
            <v-btn
              to="/login"
              variant="text"
              color="white"
            >
              ログイン
            </v-btn>
            <v-btn
              to="/register"
              variant="text"
              color="white"
            >
              登録
            </v-btn>
          </template>
        </ClientOnly>
      </div>

      <!-- モバイル用ハンバーガーメニュー -->
      <v-menu class="d-flex d-md-none">
        <template #activator="{ props }">
          <v-btn
            icon
            v-bind="props"
          >
            <v-icon>mdi-menu</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item to="/">
            <v-list-item-title>ホーム</v-list-item-title>
          </v-list-item>
          <ClientOnly>
            <template v-if="isAuthenticated">
              <v-list-item to="/favorites">
                <v-list-item-title>お気に入り</v-list-item-title>
              </v-list-item>
              <v-list-item @click="handleLogout">
                <v-list-item-title>ログアウト</v-list-item-title>
              </v-list-item>
            </template>
            <template v-else>
              <v-list-item to="/login">
                <v-list-item-title>ログイン</v-list-item-title>
              </v-list-item>
              <v-list-item to="/register">
                <v-list-item-title>登録</v-list-item-title>
              </v-list-item>
            </template>
          </ClientOnly>
        </v-list>
      </v-menu>
    </v-app-bar>

    <v-main>
      <v-container>
        <slot />
      </v-container>
    </v-main>

    <v-footer
      app
      color="primary"
      class="app-footer"
    >
      <v-row
        justify="center"
        no-gutters
      >
        <span>&copy; {{ new Date().getFullYear() }} - Reliche</span>
      </v-row>
    </v-footer>
  </v-app>
</template>

<script setup>
import { storeToRefs } from 'pinia'
import { useAuthStore } from '~/stores/auth'

const authStore = useAuthStore()
const { isAuthenticated } = storeToRefs(authStore)

const config = useRuntimeConfig()

// ログアウト処理
const handleLogout = async () => {
  try {
    await authStore.logout()
  }
  catch (error) {
    // 型安全性のための環境値の検証
    const isDevelopment = config.public.appEnv === 'development'
    if (isDevelopment) {
      console.error('ログアウトエラー:', error)
    }
  }
}
</script>

<style>
/* ヘッダーとフッターの文字色を強制的に白にして視認性を確保 */
.app-header {
  color: white !important;
}

.app-header .v-toolbar__content,
.app-header .v-app-bar-title,
.app-header .v-app-bar-title span,
.app-header .v-app-bar-title a,
.app-header .v-btn,
.app-header .v-btn .v-btn__content,
.app-header .v-btn .v-btn__content span,
.app-header .v-list-item-title {
  color: white !important;
}

.app-header .v-icon {
  color: white !important;
}

/* Vuetifyのcolorプロパティで白色が適用されない場合の追加CSS */
.app-header .v-btn--variant-text {
  color: white !important;
}

.app-footer {
  color: white !important;
}

.app-footer span {
  color: white !important;
}
</style>
