export interface NutritionCalculatorInput {
  weight: number
  height: number
  age: number
  gender: 'male' | 'female'
  activity: number
  goal: number
}

export interface NutritionCalculatorResult {
  bmr: number
  tdee: number
  targetCal: number
  carbs: number
  protein: number
  fat: number
}

export function calculateNutritionNeeds(input: NutritionCalculatorInput): NutritionCalculatorResult {
  const bmr = input.gender === 'male'
    ? Math.round((10 * input.weight) + (6.25 * input.height) - (5 * input.age) + 5)
    : Math.round((10 * input.weight) + (6.25 * input.height) - (5 * input.age) - 161)

  const tdee = Math.round(bmr * input.activity)
  const targetCal = Math.round(tdee + input.goal)

  return {
    bmr,
    tdee,
    targetCal,
    carbs: Math.round((targetCal * 0.50) / 4),
    protein: Math.round((targetCal * 0.30) / 4),
    fat: Math.round((targetCal * 0.20) / 9),
  }
}
