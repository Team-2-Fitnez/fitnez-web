import { defineStore } from 'pinia'
import { mealPlanApi } from '@/features/Mealplan/api/mealPlanApi'

interface Food {
    id: number
    food_name: string
    calories: number
}

interface MealPlan {
    daily_limit: number
    bmr: number
    tdee: number
    target_kal: number
}

export const useMealPlanStore = defineStore('mealPlan', {
    state: () => ({
        foods: [] as Food[],
        mealPlan: null as MealPlan | null,
        loading: false,
        error: '',
    }),

    getters: {
        totalCalories: (state) =>
            state.foods.reduce((sum, f) => sum + Number(f.calories), 0),
        dailyLimit: (state) => state.mealPlan?.daily_limit ?? 0,
        remaining: (state) => {
            const limit = state.mealPlan?.daily_limit ?? 0
            const total = state.foods.reduce((sum, f) => sum + Number(f.calories), 0)
            return limit - total
        },
        percentage: (state) => {
            const limit = state.mealPlan?.daily_limit ?? 0
            const total = state.foods.reduce((sum, f) => sum + Number(f.calories), 0)
            return limit > 0 ? Math.min(100, Math.round((total / limit) * 100)) : 0
        },
    },

    actions: {
        async loadAll() {
            this.loading = true
            try {
                const [planRes, foodRes] = await Promise.all([
                    mealPlanApi.getMealPlan(),
                    mealPlanApi.getFoodLog(),
                ])
                this.mealPlan = (planRes as any).plan ?? null
                this.foods = (foodRes as any).foods ?? []
            } catch {
                this.error = 'Failed to load data'
            } finally {
                this.loading = false
            }
        },

        async saveMealPlan(payload: MealPlan) {
            await mealPlanApi.saveMealPlan(payload)
            this.mealPlan = payload
        },

        async addFood(name: string, calories: number) {
            await mealPlanApi.addFood({ food_name: name, calories })
            const foodRes = await mealPlanApi.getFoodLog()
            this.foods = (foodRes as any).foods ?? []
        },

        async deleteFood(id: number) {
            await mealPlanApi.deleteFood(id)
            this.foods = this.foods.filter((f) => f.id !== id)
        },
    },
})