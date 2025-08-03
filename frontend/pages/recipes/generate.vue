<template>
  <div class="recipe-generate">
    <v-container>
      <v-row justify="center">
        <v-col
          cols="12"
          lg="10"
          xl="8"
        >
          <v-card class="pa-4 mb-4">
            <v-card-title class="text-h4 mb-4 text-center">
              <v-icon
                icon="mdi-robot-excited"
                class="mr-2"
                color="primary"
              />
              AI レシピ生成
            </v-card-title>
            <v-card-subtitle class="text-center">
              AI があなたにぴったりのレシピを提案します
            </v-card-subtitle>
          </v-card>

          <!-- 生成モード選択 -->
          <v-card class="mb-4">
            <v-card-title>生成モードを選択</v-card-title>
            <v-card-text>
              <v-chip-group
                v-model="selectedMode"
                color="primary"
                selected-class="text-white"
                mandatory
              >
                <v-chip
                  value="basic"
                  size="large"
                >
                  <v-icon
                    start
                    icon="mdi-chef-hat"
                  />
                  基本生成
                </v-chip>
                <v-chip
                  value="ingredients"
                  size="large"
                >
                  <v-icon
                    start
                    icon="mdi-food"
                  />
                  食材指定
                </v-chip>
                <v-chip
                  value="constraints"
                  size="large"
                >
                  <v-icon
                    start
                    icon="mdi-cog"
                  />
                  条件指定
                </v-chip>
              </v-chip-group>
            </v-card-text>
          </v-card>

          <!-- 基本生成モード -->
          <v-card
            v-if="selectedMode === 'basic'"
            class="mb-4"
          >
            <v-card-title>基本レシピ生成</v-card-title>
            <v-card-text>
              <v-select
                v-model="basicOptions.category"
                :items="categories"
                label="カテゴリ（任意）"
                clearable
                prepend-inner-icon="mdi-tag"
              />
            </v-card-text>
          </v-card>

          <!-- 食材指定モード -->
          <v-card
            v-if="selectedMode === 'ingredients'"
            class="mb-4"
          >
            <v-card-title>食材指定レシピ生成</v-card-title>
            <v-card-text>
              <v-combobox
                v-model="ingredientsOptions.ingredients"
                label="使いたい食材を入力"
                multiple
                chips
                closable-chips
                prepend-inner-icon="mdi-food"
                hint="食材名を入力して Enter を押してください（最大10個）"
                persistent-hint
                :rules="[ingredientsValidation]"
              />
            </v-card-text>
          </v-card>

          <!-- 条件指定モード -->
          <v-card
            v-if="selectedMode === 'constraints'"
            class="mb-4"
          >
            <v-card-title>条件指定レシピ生成</v-card-title>
            <v-card-text>
              <v-row>
                <v-col
                  cols="12"
                  md="6"
                >
                  <v-text-field
                    v-model.number="constraintsOptions.max_time"
                    label="最大調理時間（分）"
                    type="number"
                    min="5"
                    max="120"
                    prepend-inner-icon="mdi-timer"
                    clearable
                  />
                </v-col>
                <v-col
                  cols="12"
                  md="6"
                >
                  <v-select
                    v-model="constraintsOptions.difficulty"
                    :items="difficulties"
                    label="難易度"
                    clearable
                    prepend-inner-icon="mdi-chart-line"
                  />
                </v-col>
                <v-col cols="12">
                  <v-select
                    v-model="constraintsOptions.tags"
                    :items="tags"
                    label="タグ"
                    multiple
                    chips
                    clearable
                    prepend-inner-icon="mdi-tag-multiple"
                  />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <!-- 保存オプション -->
          <v-card class="mb-4">
            <v-card-text>
              <v-switch
                v-model="saveToDb"
                label="生成したレシピをデータベースに保存"
                color="primary"
                hide-details
              />
            </v-card-text>
          </v-card>

          <!-- 生成ボタン -->
          <v-card class="mb-4">
            <v-card-text class="text-center">
              <v-btn
                :loading="loading"
                :disabled="!canGenerate"
                color="primary"
                size="large"
                @click="generateRecipe"
              >
                <v-icon
                  start
                  icon="mdi-magic-staff"
                />
                レシピを生成
              </v-btn>
            </v-card-text>
          </v-card>

          <!-- 生成結果 -->
          <v-card
            v-if="generatedRecipe"
            class="mb-4"
          >
            <v-card-title class="d-flex align-center">
              <v-icon
                icon="mdi-check-circle"
                color="success"
                class="mr-2"
              />
              生成結果
            </v-card-title>
            <v-card-text>
              <div class="generated-recipe">
                <!-- レシピタイトル -->
                <div class="recipe-title-section mb-4">
                  <h1 class="recipe-title">
                    {{ generatedRecipe.data.recipe.title }}
                  </h1>
                </div>

                <!-- レシピ統計情報（通常のレシピ詳細画面と同様） -->
                <div class="recipe-info mb-4">
                  <v-row
                    class="recipe-stats-grid"
                    no-gutters
                  >
                    <v-col
                      cols="6"
                      sm="3"
                    >
                      <v-card
                        class="stat-card"
                        elevation="2"
                      >
                        <div class="stat-content">
                          <v-icon
                            color="#4CAF50"
                            size="24"
                          >
                            mdi-clock-outline
                          </v-icon>
                          <div class="stat-text">
                            <span class="stat-value">{{
                              generatedRecipe.data.recipe.cooking_time
                            }}</span>
                            <span class="stat-label">分</span>
                          </div>
                        </div>
                      </v-card>
                    </v-col>
                    <v-col
                      cols="6"
                      sm="3"
                    >
                      <v-card
                        class="stat-card"
                        elevation="2"
                      >
                        <div class="stat-content">
                          <v-icon
                            color="#FF9800"
                            size="24"
                          >
                            mdi-account-multiple
                          </v-icon>
                          <div class="stat-text">
                            <span class="stat-value">{{
                              generatedRecipe.data.recipe.servings
                            }}</span>
                            <span class="stat-label">人分</span>
                          </div>
                        </div>
                      </v-card>
                    </v-col>
                    <v-col
                      cols="6"
                      sm="3"
                    >
                      <v-card
                        class="stat-card"
                        elevation="2"
                      >
                        <div class="stat-content">
                          <v-icon
                            color="#F44336"
                            size="24"
                          >
                            mdi-fire
                          </v-icon>
                          <div class="stat-text">
                            <span class="stat-value">{{
                              generatedRecipe.data.recipe.calories || '300'
                            }}</span>
                            <span class="stat-label">kcal</span>
                          </div>
                        </div>
                      </v-card>
                    </v-col>
                    <v-col
                      cols="6"
                      sm="3"
                    >
                      <v-card
                        class="stat-card"
                        elevation="2"
                      >
                        <div class="stat-content">
                          <v-icon
                            color="#9C27B0"
                            size="24"
                          >
                            mdi-tag-outline
                          </v-icon>
                          <div class="stat-text">
                            <span class="stat-value">{{
                              generatedRecipe.data.recipe.category
                            }}</span>
                            <span class="stat-label">ジャンル</span>
                          </div>
                        </div>
                      </v-card>
                    </v-col>
                  </v-row>
                </div>

                <v-row>
                  <v-col
                    cols="12"
                    md="6"
                  >
                    <h4 class="mb-2">
                      材料
                    </h4>
                    <v-list density="compact">
                      <v-list-item
                        v-for="(ingredient, index) in generatedRecipe.data
                          .recipe_ingredients"
                        :key="index"
                        :title="ingredient.name"
                        :subtitle="ingredient.amount"
                      >
                        <template #prepend>
                          <v-icon
                            icon="mdi-food"
                            color="primary"
                          />
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-col>

                  <v-col
                    cols="12"
                    md="6"
                  >
                    <h4 class="mb-2">
                      作り方
                    </h4>
                    <v-list density="compact">
                      <v-list-item
                        v-for="(instruction, index) in generatedRecipe.data
                          .recipe.instructions"
                        :key="index"
                        :title="`${index + 1}. ${instruction}`"
                      >
                        <template #prepend>
                          <v-avatar
                            color="primary"
                            size="small"
                          >
                            {{ index + 1 }}
                          </v-avatar>
                        </template>
                      </v-list-item>
                    </v-list>
                  </v-col>
                </v-row>

                <v-row class="mt-4">
                  <v-col cols="12">
                    <v-chip-group>
                      <v-chip
                        v-for="tag in generatedRecipe.data.recipe.tags"
                        :key="tag"
                        variant="outlined"
                        size="small"
                      >
                        {{ tag }}
                      </v-chip>
                    </v-chip-group>
                  </v-col>
                </v-row>

                <v-alert
                  v-if="generatedRecipe.saved_recipe_id"
                  type="success"
                  class="mt-4"
                >
                  レシピがデータベースに保存されました（ID:
                  {{ generatedRecipe.saved_recipe_id }}）
                </v-alert>
              </div>
            </v-card-text>
          </v-card>

          <!-- エラー表示 -->
          <v-alert
            v-if="error"
            type="error"
            dismissible
            @click:close="error = null"
          >
            {{ error }}
          </v-alert>
        </v-col>
      </v-row>
    </v-container>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import type { AIRecipeResponse } from '~/types/aiRecipe'
import { useRecipeApi } from '~/composables/useRecipeApi'

// ページ設定
definePageMeta({
  title: 'AI レシピ生成',
  description: 'AI があなたにぴったりのレシピを提案します',
})

// リアクティブデータ
const selectedMode = ref<'basic' | 'ingredients' | 'constraints'>('basic')
const loading = ref(false)
const error = ref<string | null>(null)
const generatedRecipe = ref<AIRecipeResponse | null>(null)
const saveToDb = ref(false)

// 基本生成オプション
const basicOptions = ref({
  category: null as string | null,
})

// 食材指定オプション
const ingredientsOptions = ref({
  ingredients: [] as string[],
})

// 条件指定オプション
const constraintsOptions = ref({
  max_time: null as number | null,
  tags: [] as string[],
  difficulty: null as string | null,
})

// 選択肢
const categories = ['和食', '洋食', '中華', 'イタリアン', 'フレンチ', 'その他']
const difficulties = ['簡単', '普通', '難しい']
const tags = [
  '時短',
  '節約',
  'ヘルシー',
  '簡単',
  'ボリューム',
  'おつまみ',
  'デザート',
]

// API
const {
  generateBasicRecipe,
  generateRecipeByIngredients,
  generateRecipeWithConstraints,
} = useRecipeApi()

// バリデーション
const ingredientsValidation = (value: string[]) => {
  if (selectedMode.value === 'ingredients' && (!value || value.length === 0)) {
    return '食材を少なくとも1つ指定してください'
  }
  if (value && value.length > 10) {
    return '食材は最大10個まで指定できます'
  }
  return true
}

// 生成可能かどうか
const canGenerate = computed(() => {
  if (selectedMode.value === 'ingredients') {
    return (
      ingredientsOptions.value.ingredients.length > 0
      && ingredientsOptions.value.ingredients.length <= 10
    )
  }
  return true
})

// レシピ生成
const generateRecipe = async () => {
  try {
    loading.value = true
    error.value = null
    generatedRecipe.value = null

    let result: AIRecipeResponse

    switch (selectedMode.value) {
      case 'basic':
        result = await generateBasicRecipe({
          ...basicOptions.value,
          save_to_db: saveToDb.value,
        })
        break

      case 'ingredients':
        result = await generateRecipeByIngredients({
          ...ingredientsOptions.value,
          save_to_db: saveToDb.value,
        })
        break

      case 'constraints': {
        // null 値を除去
        const cleanConstraints = Object.fromEntries(
          Object.entries(constraintsOptions.value).filter(
            ([_, value]) =>
              value !== null
              && value !== undefined
              && (Array.isArray(value) ? value.length > 0 : true),
          ),
        )
        result = await generateRecipeWithConstraints({
          ...cleanConstraints,
          save_to_db: saveToDb.value,
        })
        break
      }

      default:
        throw new Error('無効な生成モードです')
    }

    generatedRecipe.value = result
  }
  catch (err: unknown) {
    const errorMessage
      = err instanceof Error ? err.message : 'レシピの生成に失敗しました'
    error.value = errorMessage
  }
  finally {
    loading.value = false
  }
}
</script>

<style scoped>
.recipe-generate {
  min-height: 100vh;
  background: linear-gradient(135deg, #e8f5e8 0%, #f0f9ff 100%);
}

.generated-recipe {
  background: white;
  border-radius: 12px;
  padding: 24px;
  box-shadow: 0 2px 8px rgb(0 0 0 / 10%);
}

.recipe-title-section {
  text-align: center;
  margin-bottom: 24px;
}

.recipe-title {
  font-size: 24px;
  font-weight: 600;
  color: #333;
  margin: 0;
}

.recipe-info {
  margin-bottom: 32px;
}

.recipe-stats-grid {
  gap: 8px;
}

.stat-card {
  background: white;
  margin: 4px;
  border-radius: 12px !important;
  transition: all 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgb(0 0 0 / 15%) !important;
}

.stat-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 16px 12px;
  gap: 8px;
}

.stat-text {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.stat-value {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  line-height: 1.2;
}

.stat-label {
  font-size: 12px;
  color: #666;
  margin-top: 2px;
}

.generated-recipe h4 {
  color: #1976d2;
  margin-bottom: 16px;
  font-size: 18px;
  font-weight: 600;
}

/* タブレット・デスクトップ対応 */
@media (width >= 768px) {
  .recipe-title {
    font-size: 28px;
  }

  .generated-recipe {
    padding: 32px;
  }
}
</style>
