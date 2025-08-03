<template>
  <v-app>
    <v-app-bar app color="primary" class="app-header">
      <!-- ロゴとタイトル -->
      <v-app-bar-title class="d-flex align-center">
        <NuxtLink
          to="/"
          class="text-decoration-none text-white d-flex align-center logo-link"
        >
          <div class="logo-container mr-2">
            <v-icon size="32" color="white">mdi-chef-hat</v-icon>
          </div>
          <div class="brand-text">
            <span class="brand-name d-none d-sm-block">Reliche</span>
            <span class="brand-tagline d-none d-md-block"
              >美味しいレシピを発見</span
            >
            <span class="d-block d-sm-none brand-initial">R</span>
          </div>
        </NuxtLink>
      </v-app-bar-title>

      <v-spacer></v-spacer>

      <!-- デスクトップ用ナビゲーション -->
      <div class="d-none d-md-flex">
        <v-btn to="/" variant="text" color="white">ホーム</v-btn>
        <ClientOnly>
          <template v-if="isAuthenticated">
            <v-btn to="/favorites" variant="text" color="white"
              >お気に入り</v-btn
            >
            <v-btn variant="text" color="white" @click="handleLogout"
              >ログアウト</v-btn
            >
          </template>
          <template v-else>
            <v-btn to="/login" variant="text" color="white">ログイン</v-btn>
            <v-btn to="/register" variant="text" color="white">登録</v-btn>
          </template>
        </ClientOnly>
      </div>

      <!-- モバイル用ハンバーガーメニュー -->
      <v-menu class="d-flex d-md-none">
        <template #activator="{ props }">
          <v-btn icon v-bind="props">
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

    <v-footer app color="primary" class="app-footer">
      <v-row justify="center" no-gutters>
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
  } catch (error) {
    // 型安全性のための環境値の検証
    const isDevelopment = config.public.appEnv === 'development'
    if (isDevelopment) {
      // eslint-disable-next-line no-console
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

/* ロゴとブランディングのスタイル */
.logo-link {
  transition: all 0.3s ease;
}

.logo-link:hover {
  transform: scale(1.05);
}

.logo-container {
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 50%;
  width: 40px;
  height: 40px;
  transition: all 0.3s ease;
}

.logo-link:hover .logo-container {
  background: rgba(255, 255, 255, 0.2);
  transform: rotate(5deg);
}

.brand-text {
  display: flex;
  flex-direction: column;
  line-height: 1.2;
}

.brand-name {
  font-size: 1.5rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.brand-tagline {
  font-size: 0.75rem;
  opacity: 0.9;
  font-weight: 400;
  margin-top: -2px;
}

.brand-initial {
  font-size: 1.8rem;
  font-weight: 700;
}
</style>
