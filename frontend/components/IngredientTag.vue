<template>
  <div class="ingredient-tag">
    <span class="tag-name">{{ ingredient.name }}</span>
    <v-btn
      @click="removeIngredient"
      icon
      size="small"
      variant="text"
      class="remove-btn"
    >
      <v-icon size="16" color="rgba(0, 0, 0, 0.6)">mdi-close</v-icon>
    </v-btn>
  </div>
</template>

<script setup lang="ts">
import type { Ingredient } from '~/stores/ingredients'

interface Props {
  ingredient: Ingredient
}

const props = defineProps<Props>()

const emit = defineEmits<{
  remove: [ingredientId: number]
}>()

const removeIngredient = () => {
  emit('remove', props.ingredient.id)
}
</script>

<style scoped>
.ingredient-tag {
  display: inline-flex;
  align-items: center;
  background-color: #e8f5e8;
  border: 1px solid #4CAF50;
  border-radius: 20px;
  padding: 8px 4px 8px 12px;
  margin: 4px;
  font-size: 14px;
  color: #2e7d32;
  transition: all 0.2s ease;
}

.ingredient-tag:hover {
  background-color: #dcedc8;
  border-color: #388e3c;
}

.tag-name {
  margin-right: 4px;
  font-weight: 500;
}

.remove-btn {
  margin-left: 4px;
  border-radius: 50%;
  width: 24px;
  height: 24px;
  min-width: 24px;
}

.remove-btn:hover {
  background-color: rgba(0, 0, 0, 0.08);
}
</style>