<template>
  <div class="recipe-card" @click="goToRecipe">
    <div class="recipe-image">
      <div class="placeholder-image">
        <v-icon size="48" color="rgba(0, 0, 0, 0.3)">mdi-image</v-icon>
      </div>
    </div>
    
    <div class="recipe-content">
      <div class="recipe-header">
        <h3 class="recipe-name">{{ recipe.name }}</h3>
        <div class="recipe-time">
          <v-icon size="16" color="#666">mdi-clock-outline</v-icon>
          <span>{{ recipe.cooking_time }}分</span>
        </div>
      </div>
      
      <div class="recipe-status">
        <v-icon 
          :color="recipe.missing_count === 0 ? '#4CAF50' : '#FF9800'" 
          size="16"
        >
          {{ recipe.missing_count === 0 ? 'mdi-check-circle' : 'mdi-alert' }}
        </v-icon>
        <span 
          :class="['status-text', { 'available': recipe.missing_count === 0 }]"
        >
          {{ recipe.status }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Recipe } from '~/types/recipe'

interface Props {
  recipe: Recipe
}

const props = defineProps<Props>()

const goToRecipe = () => {
  navigateTo(`/recipes/${props.recipe.id}`)
}
</script>

<style scoped>
.recipe-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  overflow: hidden;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-bottom: 16px;
}

.recipe-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
  transform: translateY(-2px);
}

.recipe-image {
  height: 120px;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.placeholder-image {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
}

.recipe-content {
  padding: 16px;
}

.recipe-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
}

.recipe-name {
  font-size: 16px;
  font-weight: 600;
  color: #333;
  margin: 0;
  line-height: 1.3;
  flex: 1;
  margin-right: 12px;
}

.recipe-time {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  color: #666;
  white-space: nowrap;
}

.recipe-status {
  display: flex;
  align-items: center;
  gap: 8px;
}

.status-text {
  font-size: 14px;
  color: #FF9800;
  font-weight: 500;
}

.status-text.available {
  color: #4CAF50;
}

/* タブレット・デスクトップ対応 */
@media (min-width: 768px) {
  .recipe-card {
    margin-bottom: 20px;
  }
  
  .recipe-image {
    height: 140px;
  }
  
  .recipe-content {
    padding: 20px;
  }
  
  .recipe-name {
    font-size: 18px;
  }
}
</style>