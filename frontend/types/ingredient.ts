export interface Ingredient {
  id: number
  name: string
}

export interface IngredientSuggestion extends Ingredient {
  isPopular: boolean
}
