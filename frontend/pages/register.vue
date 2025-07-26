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
          <strong>登録エラー</strong><br />
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
          <strong>登録成功</strong><br />
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
          aria-label="お名前を入力してください"
          aria-describedby="name-help"
          aria-invalid="nameError ? 'true' : 'false'"
          @input="clearNameError"
        />
        <div id="name-help" class="sr-only">
          2文字以上255文字以下で入力してください
        </div>

        <v-text-field
          v-model="form.email"
          label="メールアドレス"
          type="email"
          :error-messages="emailError"
          :disabled="loading"
          required
          autocomplete="email"
          prepend-inner-icon="mdi-email"
          aria-label="メールアドレスを入力してください"
          aria-describedby="email-help"
          aria-invalid="emailError ? 'true' : 'false'"
          @input="clearEmailError"
        />
        <div id="email-help" class="sr-only">
          有効なメールアドレス形式で入力してください
        </div>

        <v-text-field
          v-model="form.password"
          label="パスワード"
          type="password"
          :error-messages="passwordError"
          :disabled="loading"
          required
          autocomplete="new-password"
          prepend-inner-icon="mdi-lock"
          aria-label="パスワードを入力してください"
          aria-describedby="password-help password-strength"
          aria-invalid="passwordError ? 'true' : 'false'"
          @input="clearPasswordError"
        />
        <div id="password-help" class="sr-only">
          8文字以上で大文字・小文字・数字・特殊文字を含むパスワードを入力してください
        </div>
        <div
          v-if="passwordStrength.feedback.length > 0"
          id="password-strength"
          class="text-caption mb-2"
          :class="passwordStrength.score >= 4 ? 'text-success' : 'text-warning'"
          role="status"
          aria-live="polite"
        >
          強度: {{ passwordStrength.score }}/5
          <span v-if="passwordStrength.feedback.length > 0">
            - {{ passwordStrength.feedback.join(', ') }}
          </span>
        </div>

        <v-text-field
          v-model="form.passwordConfirmation"
          label="パスワード（確認）"
          type="password"
          :error-messages="passwordConfirmationError"
          :disabled="loading"
          required
          autocomplete="new-password"
          prepend-inner-icon="mdi-lock-check"
          aria-label="パスワード確認を入力してください"
          aria-describedby="password-confirm-help"
          aria-invalid="passwordConfirmationError ? 'true' : 'false'"
          @input="clearPasswordConfirmationError"
        />
        <div id="password-confirm-help" class="sr-only">
          上記で入力したパスワードと同じものを入力してください
        </div>

        <div class="mt-6">
          <v-btn
            type="submit"
            color="primary"
            variant="flat"
            size="large"
            :loading="loading"
            :disabled="loading"
            block
            class="mb-4"
            aria-label="ユーザー登録を実行"
            :aria-describedby="loading ? 'loading-message' : undefined"
          >
            <v-icon left class="mr-2">mdi-account-plus</v-icon>
            アカウントを作成する
          </v-btn>
          <div
            v-if="loading"
            id="loading-message"
            class="sr-only"
            role="status"
            aria-live="polite"
          >
            アカウント作成中です。しばらくお待ちください。
          </div>

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

<script setup lang="ts">
import { ref, computed, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '~/stores/auth'
import type {
  RegisterCredentials,
  ValidationErrors,
  FormState,
} from '~/types/auth'
import { useValidation } from '~/composables/useValidation'

definePageMeta({
  middleware: 'guest', // 認証済みユーザーはリダイレクト
})

const authStore = useAuthStore()
const router = useRouter()
const {
  validateForm: validateFormData,
  validatePasswordConfirmation,
  checkPasswordStrength,
  createDebouncedValidator,
  registerValidationRules,
} = useValidation()

// 型安全なフォームデータ
const form = ref<RegisterCredentials & { passwordConfirmation: string }>({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  passwordConfirmation: '',
})

// 型安全な状態管理
const formState = ref<FormState>({
  loading: false,
  errors: {},
  touched: {},
})

// 個別エラーメッセージ（Computed for type safety）
const nameError = computed(() => formState.value.errors.name?.[0] || '')
const emailError = computed(() => formState.value.errors.email?.[0] || '')
const passwordError = computed(() => formState.value.errors.password?.[0] || '')
const passwordConfirmationError = computed(
  () => formState.value.errors.password_confirmation?.[0] || ''
)

// 後方互換性のためのエイリアス
const loading = computed(() => formState.value.loading)
const errorMessage = ref('')
const successMessage = ref('')

// パスワード強度表示
const passwordStrength = ref({ score: 0, feedback: [] })

// セキュリティ強化されたバリデーション関数
const validateForm = (): boolean => {
  // フォームデータのバリデーション
  const errors = validateFormData(form.value, registerValidationRules)

  // パスワード確認のバリデーション
  const confirmationError = validatePasswordConfirmation(
    form.value.password,
    form.value.passwordConfirmation
  )

  if (confirmationError) {
    errors.password_confirmation = [confirmationError]
  }

  // エラー状態を更新
  formState.value.errors = errors

  return Object.keys(errors).length === 0
}

// リアルタイムバリデーション（デバウンス付き）
const clearFieldError = (fieldName: keyof ValidationErrors) => {
  if (formState.value.errors[fieldName]) {
    delete formState.value.errors[fieldName]
  }
}

// パスワード強度チェック（リアルタイム）
const updatePasswordStrength = () => {
  if (form.value.password) {
    passwordStrength.value = checkPasswordStrength(form.value.password)
  } else {
    passwordStrength.value = { score: 0, feedback: [] }
  }
}

// デバウンス付きバリデーション
const debouncedPasswordValidation = createDebouncedValidator(
  (value: string) => {
    updatePasswordStrength()
    return ''
  },
  300
)

// エラークリア関数（後方互換性）
const clearNameError = () => clearFieldError('name')
const clearEmailError = () => clearFieldError('email')
const clearPasswordError = () => {
  clearFieldError('password')
  updatePasswordStrength()
}
const clearPasswordConfirmationError = () =>
  clearFieldError('password_confirmation')

// パフォーマンス最適化された登録処理
const register = async (): Promise<void> => {
  if (!validateForm()) return

  // ローディング状態開始
  formState.value.loading = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    // 型安全な登録データ
    const registerData: RegisterCredentials = {
      name: form.value.name.trim(),
      email: form.value.email.trim().toLowerCase(),
      password: form.value.password,
      password_confirmation: form.value.passwordConfirmation,
    }

    const result = await authStore.register(
      registerData.name,
      registerData.email,
      registerData.password,
      registerData.password_confirmation
    )

    if (result.success) {
      successMessage.value =
        'アカウントが作成されました！自動的にログインします...'

      // パフォーマンス: nextTick後にリダイレクト
      await nextTick()
      setTimeout(() => {
        router.push('/')
      }, 1500) // 1.5秒に短縮
    } else if (result.errors) {
      // サーバーエラーの詳細表示
      formState.value.errors = result.errors as ValidationErrors
    } else {
      errorMessage.value = result.message || '登録に失敗しました'
    }
  } catch (error: unknown) {
    console.error('Registration error:', error)

    // 型安全なエラーハンドリング
    if (error && typeof error === 'object' && 'response' in error) {
      const axiosError = error as {
        response?: { data?: { errors?: ValidationErrors } }
      }
      if (axiosError.response?.data?.errors) {
        formState.value.errors = axiosError.response.data.errors
      } else {
        errorMessage.value = '登録中にエラーが発生しました'
      }
    } else if (error instanceof Error) {
      errorMessage.value = error.message || '登録中にエラーが発生しました'
    } else {
      errorMessage.value = '登録中にエラーが発生しました'
    }
  } finally {
    formState.value.loading = false
  }
}
</script>

<style scoped>
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>
