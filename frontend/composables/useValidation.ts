import type {
  ValidationErrors,
  ValidationRule,
  ValidationRules,
} from '~/types/auth'

// 強化されたメールバリデーション（RFC 5322準拠）
const emailRegex
  = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/

// パスワード強度チェック（最低8文字、大文字・小文字・数字・特殊文字を含む）
const strongPasswordRegex
  = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/

// バリデーションルール定義
export const registerValidationRules: ValidationRules = {
  name: [
    { required: true, message: 'お名前を入力してください' },
    { minLength: 2, message: 'お名前は2文字以上で入力してください' },
    { maxLength: 255, message: 'お名前は255文字以下で入力してください' },
  ],
  email: [
    { required: true, message: 'メールアドレスを入力してください' },
    { pattern: emailRegex, message: '有効なメールアドレスを入力してください' },
    {
      maxLength: 255,
      message: 'メールアドレスは255文字以下で入力してください',
    },
  ],
  password: [
    { required: true, message: 'パスワードを入力してください' },
    { minLength: 8, message: 'パスワードは8文字以上で入力してください' },
    { maxLength: 255, message: 'パスワードは255文字以下で入力してください' },
  ],
  password_confirmation: [
    { required: true, message: 'パスワード（確認）を入力してください' },
  ],
}

// 強力なパスワードのバリデーションルール
export const strongPasswordRules: ValidationRule[] = [
  { required: true, message: 'パスワードを入力してください' },
  { minLength: 8, message: 'パスワードは8文字以上で入力してください' },
  {
    pattern: strongPasswordRegex,
    message: 'パスワードは大文字・小文字・数字・特殊文字を含む必要があります',
  },
]

export function useValidation() {
  // 単一フィールドのバリデーション
  const validateField = (value: string, rules: ValidationRule[]): string => {
    for (const rule of rules) {
      if (rule.required && !value.trim()) {
        return rule.message
      }

      if (value && rule.minLength && value.length < rule.minLength) {
        return rule.message
      }

      if (value && rule.maxLength && value.length > rule.maxLength) {
        return rule.message
      }

      if (value && rule.pattern && !rule.pattern.test(value)) {
        return rule.message
      }
    }
    return ''
  }

  // フォーム全体のバリデーション
  const validateForm = (
    formData: Record<string, unknown>,
    rules: ValidationRules,
  ): ValidationErrors => {
    const errors: ValidationErrors = {}

    for (const [field, fieldRules] of Object.entries(rules)) {
      const error = validateField(formData[field] || '', fieldRules)
      if (error) {
        errors[field] = [error]
      }
    }

    return errors
  }

  // パスワード確認のバリデーション
  const validatePasswordConfirmation = (
    password: string,
    confirmation: string,
  ): string => {
    if (!confirmation.trim()) {
      return 'パスワード（確認）を入力してください'
    }
    if (password !== confirmation) {
      return 'パスワードが一致しません'
    }
    return ''
  }

  // パスワード強度チェック
  const checkPasswordStrength = (
    password: string,
  ): { score: number, feedback: string[] } => {
    const feedback: string[] = []
    let score = 0

    if (password.length >= 8) score += 1
    else feedback.push('8文字以上にしてください')

    if (/[a-z]/.test(password)) score += 1
    else feedback.push('小文字を含めてください')

    if (/[A-Z]/.test(password)) score += 1
    else feedback.push('大文字を含めてください')

    if (/\d/.test(password)) score += 1
    else feedback.push('数字を含めてください')

    if (/[@$!%*?&]/.test(password)) score += 1
    else feedback.push('特殊文字（@$!%*?&）を含めてください')

    return { score, feedback }
  }

  // リアルタイムバリデーション（デバウンス付き）
  const createDebouncedValidator = (
    validateFn: (value: string) => string,
    delay: number = 300,
  ) => {
    let timeoutId: NodeJS.Timeout | null = null

    return (value: string, callback: (error: string) => void) => {
      if (timeoutId) {
        clearTimeout(timeoutId)
      }

      timeoutId = setTimeout(() => {
        const error = validateFn(value)
        callback(error)
      }, delay)
    }
  }

  return {
    validateField,
    validateForm,
    validatePasswordConfirmation,
    checkPasswordStrength,
    createDebouncedValidator,
    registerValidationRules,
    strongPasswordRules,
  }
}
