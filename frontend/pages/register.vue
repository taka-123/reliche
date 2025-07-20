<template>
  <div class="d-flex align-center justify-center" style="min-height: 100vh">
    <v-card
      class="pa-8 mx-4 mx-sm-auto"
      max-width="450"
      width="100%"
      elevation="4"
    >
      <v-card-title class="text-h4 mb-6 text-center">ユーザー登録</v-card-title>

      <v-form @submit.prevent="register">
        <v-alert 
          v-if="errorMessage" 
          type="error" 
          variant="tonal"
          class="mb-4"
          prominent
          border="start"
        >
          <strong>登録エラー</strong><br>
          {{ errorMessage }}
        </v-alert>

        <v-alert 
          v-if="successMessage" 
          type="success"
          variant="tonal"
          class="mb-4"
          prominent
          border="start"
        >
          <strong>登録成功</strong><br>
          {{ successMessage }}
        </v-alert>

        <v-text-field
          v-model="form.name"
          label="お名前"
          :error-messages="nameError"
          :disabled="loading"
          required
          autocomplete="name"
          prepend-inner-icon="mdi-account"
          @input="clearNameError"
        />

        <v-text-field
          v-model="form.email"
          label="メールアドレス"
          type="email"
          :error-messages="emailError"
          :disabled="loading"
          required
          autocomplete="email"
          prepend-inner-icon="mdi-email"
          @input="clearEmailError"
        />

        <v-text-field
          v-model="form.password"
          label="パスワード"
          type="password"
          :error-messages="passwordError"
          :disabled="loading"
          required
          autocomplete="new-password"
          prepend-inner-icon="mdi-lock"
          @input="clearPasswordError"
        />

        <v-text-field
          v-model="form.passwordConfirmation"
          label="パスワード（確認）"
          type="password"
          :error-messages="passwordConfirmationError"
          :disabled="loading"
          required
          autocomplete="new-password"
          prepend-inner-icon="mdi-lock-check"
          @input="clearPasswordConfirmationError"
        />

        <div class="mt-6">
          <v-btn
            type="submit"
            color="success"
            variant="flat"
            size="large"
            :loading="loading"
            :disabled="loading"
            block
            class="mb-4 font-weight-bold white--text"
            style="background: linear-gradient(45deg, #4caf50, #2e7d32) !important; color: white !important;"
          >
            <v-icon left class="mr-2">mdi-account-plus</v-icon>
            アカウントを作成する
          </v-btn>

          <div class="text-center">
            <NuxtLink to="/login" class="text-decoration-none text-body-2">
              すでにアカウントをお持ちの方はログイン
            </NuxtLink>
          </div>
        </div>
      </v-form>
    </v-card>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '~/stores/auth'
import { useRouter } from 'vue-router'

definePageMeta({
  middleware: 'guest', // 認証済みユーザーはリダイレクト
})

const authStore = useAuthStore()
const router = useRouter()

// フォームデータ
const form = ref({
  name: '',
  email: '',
  password: '',
  passwordConfirmation: ''
})

// 状態管理
const loading = ref(false)
const errorMessage = ref('')
const successMessage = ref('')

// 個別エラーメッセージ
const nameError = ref('')
const emailError = ref('')
const passwordError = ref('')
const passwordConfirmationError = ref('')

// バリデーション関数
const validateForm = () => {
  let isValid = true

  // 名前のバリデーション
  if (!form.value.name) {
    nameError.value = 'お名前を入力してください'
    isValid = false
  } else if (form.value.name.length < 2) {
    nameError.value = 'お名前は2文字以上で入力してください'
    isValid = false
  } else if (form.value.name.length > 255) {
    nameError.value = 'お名前は255文字以下で入力してください'
    isValid = false
  }

  // メールアドレスのバリデーション
  if (!form.value.email) {
    emailError.value = 'メールアドレスを入力してください'
    isValid = false
  } else if (!/\S+@\S+\.\S+/.test(form.value.email)) {
    emailError.value = '有効なメールアドレスを入力してください'
    isValid = false
  }

  // パスワードのバリデーション
  if (!form.value.password) {
    passwordError.value = 'パスワードを入力してください'
    isValid = false
  } else if (form.value.password.length < 8) {
    passwordError.value = 'パスワードは8文字以上である必要があります'
    isValid = false
  }

  // パスワード確認のバリデーション
  if (!form.value.passwordConfirmation) {
    passwordConfirmationError.value = 'パスワード（確認）を入力してください'
    isValid = false
  } else if (form.value.password !== form.value.passwordConfirmation) {
    passwordConfirmationError.value = 'パスワードが一致しません'
    isValid = false
  }

  return isValid
}

// エラーメッセージをクリア
const clearNameError = () => {
  nameError.value = ''
}

const clearEmailError = () => {
  emailError.value = ''
}

const clearPasswordError = () => {
  passwordError.value = ''
}

const clearPasswordConfirmationError = () => {
  passwordConfirmationError.value = ''
}

// 登録処理
const register = async () => {
  if (!validateForm()) return

  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const result = await authStore.register(
      form.value.name,
      form.value.email,
      form.value.password,
      form.value.passwordConfirmation
    )

    if (result.success) {
      successMessage.value = 'アカウントが作成されました！自動的にログインします...'
      
      // 成功時は2秒後にホームページにリダイレクト
      setTimeout(() => {
        router.push('/')
      }, 2000)
    } else {
      errorMessage.value = result.message || '登録に失敗しました'
    }
  } catch (error) {
    console.error('Registration error:', error)
    errorMessage.value = error.message || '登録中にエラーが発生しました'
  } finally {
    loading.value = false
  }
}
</script>
