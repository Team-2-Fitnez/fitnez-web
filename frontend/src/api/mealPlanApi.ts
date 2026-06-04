import { http } from './http'

export const mealPlanApi = {
    getMealPlan() {
        return http.get<{
            plan: {
                daily_limit: number
                bmr: number
                tdee: number
                target_kal: number
            } | null
        }>('/user/meal_plan')
    },

    saveMealPlan(payload: {
        daily_limit: number
        bmr: number
        tdee: number
        target_kal: number
    }) {
        return http.put('/user/meal_plan', payload)
    },

    getFoodLog() {
        return http.get<{
            foods: { id: number; food_name: string; calories: number }[]
        }>('/user/food_log')
    },

    addFood(payload: { food_name: string; calories: number }) {
        return http.post<{ id: number; food_name: string; calories: number }>(
            '/user/food_log',
            payload
        )
    },

    deleteFood(id: number) {
        return http.delete(`/user/food_log/${id}`)
    },

    // Untuk admin monitoring
    getAllMemberNutrition() {
        return http.get<{
            members: {
                id: number
                name: string
                email: string
                daily_limit: number
                total_calories: number
            }[]
        }>('/admin/nutrition-monitoring')
    },
}