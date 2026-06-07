import type { FitnezUser } from './auth'
import type { Paginated } from './pagination'

export type Exercise = {
  id: number
  name: string
  muscle_group?: string | null
  equipment?: string | null
  tutorial_video_url?: string | null
  tutorial_image_url?: string | null
  instructions?: string | null
}

export type WorkoutExercise = {
  id: number
  workout_plan_id: number
  exercise_id: number
  day_of_week: number
  sets: number
  reps: number
  rest_seconds?: number | null
  notes?: string | null
  exercise?: Exercise | null
  workout_plan?: WorkoutPlan | null
}

export type WorkoutPlan = {
  id: number
  user_id: number
  name: string
  category: string
  date: string
  day?: string | null
  set: number
  weight: number
  reps: number
  duration: number
  completed: boolean
  status?: string | null
}

export type WorkoutTracking = {
  id: number
  user_id: number
  workout_exercise_id: number
  workout_date: string
  actual_sets?: number | null
  actual_reps?: number | null
  actual_weight_kg?: number | null
  is_completed: boolean
  logged_at: string
  workout_exercise?: WorkoutExercise | null
}

export type NutritionCalculation = {
  id: number
  user_id: number
  bmr?: string | number | null
  tdee?: string | number | null
  target_calories?: string | number | null
  target_protein?: string | number | null
  target_carbs?: string | number | null
  target_fat?: string | number | null
  calculated_at: string
}

export type Meal = {
  id: number
  meal_plan_id: number
  meal_type: string
  food_name: string
  portion_grams?: number | null
  calories?: string | number | null
  protein?: string | number | null
  carbs?: string | number | null
  fat?: string | number | null
}

export type MealPlan = {
  id: number
  user_id: number
  daily_limit?: number | string | null
  bmr?: number | string | null
  tdee?: number | string | null
  target_kal?: number | string | null
  meals?: Meal[]
}

export type TrainerMonitoringMember = FitnezUser & {
  workout_plans_count?: number
  workout_trackings_count?: number
  meal_plans_count?: number
  latest_nutrition_calculated_at?: string | null
}

export type TrainerMonitoringSummary = {
  total_members: number
  total_members_trend?: number
  active_workout_plans: number
  active_workout_plans_trend?: number
  completed_trackings: number
  completed_trackings_trend?: number
  meal_plans: number
  meal_plans_trend?: number
  nutrition_calculations: number
}

export type FoodLog = {
  id: number
  user_id: number
  food_name: string
  calories: number
  logged_date: string
  created_at: string
}

export type TrainerMemberFitnessDetail = {
  member: TrainerMonitoringMember
  summary: {
    workout_plans: number
    active_workout_plans: number
    completed_trackings: number
    incomplete_trackings: number
    meal_plans: number
    latest_nutrition_calculated_at?: string | null
  }
  workout_plans: WorkoutPlan[]
  workout_trackings: WorkoutTracking[]
  nutrition?: NutritionCalculation | null
  meal_plans: MealPlan[]
  meal_plan?: MealPlan | null
  food_logs?: FoodLog[]
}

export type TrainerMonitoringMemberPage = Paginated<TrainerMonitoringMember>
