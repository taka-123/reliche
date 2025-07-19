<template>
  <v-container>
    <v-card class="mb-6 pa-4 mx-auto" max-width="600px">
      <v-card-title class="text-h4 font-weight-bold mb-4 text-center"
        >ユーザー登録</v-card-title
      >

      <v-alert 
        v-if="errorMessage" 
        type="error" 
        variant="tonal" 
        class="mb-4"
        @click:close="clearError"
        closable
      >
        {{ errorMessage }}
      </v-alert>

      <v-alert 
        v-if="successMessage" 
        type="success" 
        variant="tonal" 
        class="mb-4"
      >
        {{ successMessage }}
      </v-alert>

      <v-form @submit.prevent="register">
        <v-text-field
          v-model="form.name"
          label="お名前"
          :rules="nameRules"
          :disabled="loading"
          variant="outlined"
          class="mb-3"
          required
        />

        <v-text-field
          v-model="form.email"
          label="メールアドレス"
          type="email"
          :rules="emailRules"
          :disabled="loading"
          variant="outlined"
          class="mb-3"
          required
        />

        <v-text-field
          v-model="form.password"
          label="パスワード"
          type="password"
          :rules="passwordRules"
          :disabled="loading"
          variant="outlined"
          class="mb-3"
          required
        />

        <v-text-field
          v-model="form.passwordConfirmation"
          label="パスワード（確認）"
          type="password"
          :rules="passwordConfirmationRules"
          :disabled="loading"
          variant="outlined"
          class="mb-4"
          required
        />

        <v-btn
          type="submit"
          color="primary"
          block
          size="large"
          :loading="loading"
          :disabled="!isFormValid"
          class="mb-4"
        >
          アカウントを作成する
        </v-btn>
      </v-form>

      <v-divider class="my-4"></v-divider>

      <div class="text-center">
        <p class="text-body-2 mb-3">
          すでにアカウントをお持ちですか？
        </p>
        <v-btn 
          color="secondary" 
          variant="outlined" 
          to="/login" 
          :disabled="loading"
        >
          ログインページへ
        </v-btn>
      </div>
    </v-card>
  </v-container>
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

// バリデーションルール
const nameRules = [
  (v) => !!v || 'お名前は必須です',
  (v) => (v && v.length >= 2) || 'お名前は2文字以上で入力してください',
  (v) => (v && v.length <= 255) || 'お名前は255文字以下で入力してください'
]

const emailRules = [
  (v) => !!v || 'メールアドレスは必須です',
  (v) => /.+@.+\..+/.test(v) || 'メールアドレスの形式が正しくありません'
]

const passwordRules = [
  (v) => !!v || 'パスワードは必須です',
  (v) => (v && v.length >= 8) || 'パスワードは8文字以上で入力してください'
]

const passwordConfirmationRules = [
  (v) => !!v || 'パスワード（確認）は必須です',
  (v) => v === form.value.password || 'パスワードが一致しません'
]

// フォームの有効性チェック
const isFormValid = computed(() => {
  return (
    form.value.name.length >= 2 &&
    /.+@.+\..+/.test(form.value.email) &&
    form.value.password.length >= 8 &&
    form.value.password === form.value.passwordConfirmation
  )
})

// 登録処理
const register = async () => {
  if (!isFormValid.value) {
    errorMessage.value = '入力内容を確認してください'
    return
  }

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

// エラーメッセージクリア
const clearError = () => {
  errorMessage.value = ''
  authStore.clearError()
}
</script>
