import { defineStore } from 'pinia'
import type { Ingredient } from '~/types/ingredient'

export const useIngredientsStore = defineStore('ingredients', {
  state: () => ({
    selectedIngredients: [] as Ingredient[],
    searchQuery: '',
    searchResults: [] as Ingredient[],
    isSearching: false,
  }),

  getters: {
    selectedCount: state => state.selectedIngredients.length,
    selectedIngredientIds: state =>
      state.selectedIngredients.map(ingredient => ingredient.id),
    hasSelectedIngredients: state => state.selectedIngredients.length > 0,
    isIngredientSelected: state => (ingredientId: number) => {
      return state.selectedIngredients.some(
        ingredient => ingredient.id === ingredientId,
      )
    },
  },

  actions: {
    addIngredient(ingredient: Ingredient) {
      if (!this.isIngredientSelected(ingredient.id)) {
        this.selectedIngredients.push(ingredient)
      }
    },

    removeIngredient(ingredientId: number) {
      this.selectedIngredients = this.selectedIngredients.filter(
        ingredient => ingredient.id !== ingredientId,
      )
    },

    clearSelectedIngredients() {
      this.selectedIngredients = []
    },

    setSearchQuery(query: string) {
      this.searchQuery = query
    },

    setSearchResults(results: Ingredient[]) {
      this.searchResults = results
    },

    setIsSearching(isSearching: boolean) {
      this.isSearching = isSearching
    },

    clearSearchResults() {
      this.searchResults = []
      this.searchQuery = ''
    },
  },
})
