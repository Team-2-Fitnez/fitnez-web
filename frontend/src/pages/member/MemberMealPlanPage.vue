<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import FitnezInput from '../../components/ui/FitnezInput.vue'
import FitnezButton from '../../components/ui/FitnezButton.vue'
import { useMealPlanStore } from '../../stores/mealPlanStore'

const store = useMealPlanStore()

const form = ref({
  weight: '',
  height: '',
  age: '',
  gender: 'male',
  activity: 1.55,
  goal: 0,
})

const result = ref<null | {
  bmr: number
  tdee: number
  targetCal: number
  carbs: number
  protein: number
  fat: number
}>(null)

const calculatorError = ref('')
const saveSuccess = ref('')
const newFood = ref({ name: '', calories: '' })
const foodError = ref('')
const showLimitModal = ref(false)

const caloriePercent = computed(() => Math.min(100, Math.max(0, Number(store.percentage || 0))))
const calorieRingStyle = computed(() => ({
  background: `conic-gradient(#0068d8 ${caloriePercent.value}%, #e7edf5 0)`,
}))

const baseGoal = computed(() => store.dailyLimit || result.value?.targetCal || 0)
const remainingCalories = computed(() => Math.max(0, store.remaining || 0))
const recentFoods = computed(() => [...store.foods].reverse())

onMounted(() => {
  store.loadAll()
})

function calculate() {
  calculatorError.value = ''
  const { weight, height, age, gender, activity, goal } = form.value
  if (!weight || !height || !age) {
    calculatorError.value = 'Please fill in all fields.'
    return
  }
  const b = Number(weight), t = Number(height), u = Number(age)
  const bmr = gender === 'male'
    ? Math.round((10 * b) + (6.25 * t) - (5 * u) + 5)
    : Math.round((10 * b) + (6.25 * t) - (5 * u) - 161)
  const tdee = Math.round(bmr * Number(activity))
  const targetCal = Math.round(tdee + Number(goal))
  result.value = {
    bmr, tdee, targetCal,
    carbs: Math.round((targetCal * 0.50) / 4),
    protein: Math.round((targetCal * 0.30) / 4),
    fat: Math.round((targetCal * 0.20) / 9),
  }
}

async function useAsLimit() {
  if (!result.value) return
  saveSuccess.value = ''
  try {
    await store.saveMealPlan({
      daily_limit: result.value.targetCal,
      bmr: result.value.bmr,
      tdee: result.value.tdee,
      target_kal: result.value.targetCal,
    })
    saveSuccess.value = 'Daily limit successfully updated.'
  } catch {
    calculatorError.value = 'Failed to save.'
  }
}

async function addFood() {
  foodError.value = ''
  if (store.dailyLimit <= 0) {
    showLimitModal.value = true
    return
  }
  if (!newFood.value.name || !newFood.value.calories || Number(newFood.value.calories) <= 0) {
    foodError.value = 'Please fill in all fields correctly.'
    return
  }
  try {
    await store.addFood(newFood.value.name, Number(newFood.value.calories))
    newFood.value = { name: '', calories: '' }
  } catch {
    foodError.value = 'Failed to add food.'
  }
}

async function deleteFood(id: number) {
  try {
    await store.deleteFood(id)
  } catch {
    foodError.value = 'Failed to delete food.'
  }
}
</script>

<template>
  <WorkspaceLayout
    role="member"
    sidebar-title="Member"
    title="Meal Plan"
    subtitle="Nutrition calculator and daily food logging."
    :sidebar-items="memberSidebarItems"
  >
    <section class="nutrition-page">
      <div class="nutrition-grid">
        <article class="calorie-card surface-card">
          <div class="section-title-row">
            <h2>Daily Calories</h2>
            <span>Daily Limit</span>
          </div>

          <div class="calorie-content">
            <div class="calorie-ring-wrap">
              <div class="calorie-ring" :style="calorieRingStyle">
                <div>
                  <strong>{{ store.totalCalories.toLocaleString('en-US') }}</strong>
                  <span>KCAL IN</span>
                </div>
              </div>
            </div>

            <div class="calorie-stats">
              <div>
                <small>Base Goal</small>
                <strong>{{ baseGoal.toLocaleString('en-US') }}</strong>
              </div>
              <div>
                <small>In</small>
                <strong class="blue">+{{ store.totalCalories.toLocaleString('en-US') }}</strong>
              </div>
              <div>
                <small>Remaining</small>
                <strong>{{ remainingCalories.toLocaleString('en-US') }}</strong>
              </div>
            </div>
          </div>
        </article>

        <article class="calculator-panel surface-card">
          <div class="calculator-title">
            <span class="material-symbols-outlined">calculate</span>
            <h2>Daily Calorie Calculator</h2>
          </div>

          <form class="calculator-form" @submit.prevent="calculate">
            <FitnezInput v-model="form.weight" label="Body Weight (kg)" type="number" placeholder="70" />
            <FitnezInput v-model="form.height" label="Body Height (cm)" type="number" placeholder="170" />
            <FitnezInput v-model="form.age" label="Age" type="number" placeholder="25" />
            <label class="select-field">
              <span>Gender</span>
              <select v-model="form.gender">
                <option value="male">Male</option>
                <option value="female">Female</option>
              </select>
            </label>
            <label class="select-field wide">
              <span>Activity Level</span>
              <select v-model.number="form.activity">
                <option value="1.2">Sedentary</option>
                <option value="1.375">Lightly active (1-3x/week)</option>
                <option value="1.55">Moderately active (3-5x/week)</option>
                <option value="1.725">Very active (6-7x/week)</option>
                <option value="1.9">Super active</option>
              </select>
            </label>
            <label class="select-field wide">
              <span>Goal</span>
              <select v-model.number="form.goal">
                <option value="-500">Weight Loss</option>
                <option value="0">Weight Maintenance</option>
                <option value="300">Weight Gain</option>
              </select>
            </label>
            <p v-if="calculatorError" class="form-error wide">{{ calculatorError }}</p>
            <button class="primary-action wide" type="submit">
              <span class="material-symbols-outlined">calculate</span>
              Calculate Nutrition Needs
            </button>
          </form>

          <div v-if="result" class="result-strip">
            <div><span>BMR</span><strong>{{ result.bmr.toLocaleString('en-US') }}</strong></div>
            <div><span>TDEE</span><strong>{{ result.tdee.toLocaleString('en-US') }}</strong></div>
            <div><span>Goal</span><strong>{{ result.targetCal.toLocaleString('en-US') }}</strong></div>
            <button type="button" @click="useAsLimit">Use as Daily Limit</button>
          </div>
          <p v-if="saveSuccess" class="save-success">{{ saveSuccess }}</p>
        </article>

        <article class="meals-card surface-card">
          <div class="meals-head">
            <h2>Today's Food</h2>
            <span>{{ store.foods.length }} items</span>
          </div>

          <div class="meal-list">
            <div v-for="food in recentFoods" :key="food.id" class="meal-item">
              <div class="meal-top">
                <strong>{{ food.food_name }}</strong>
                <span>{{ Number(food.calories).toLocaleString('en-US') }} kcal</span>
              </div>
              <p>Logged in daily meal plan</p>
              <button type="button" @click="deleteFood(food.id)">Delete</button>
            </div>

            <form class="meal-add-card" @submit.prevent="addFood">
              <FitnezInput v-model="newFood.name" label="Food Name" placeholder="e.g. Oatmeal" />
              <FitnezInput v-model="newFood.calories" label="Calories" type="number" placeholder="350" />
              <p v-if="foodError" class="form-error">{{ foodError }}</p>
              <button type="submit">
                <span class="material-symbols-outlined">add_circle</span>
                Add Food
              </button>
            </form>
          </div>
        </article>
      </div>
    </section>

    <Transition name="fade">
      <div v-if="showLimitModal" class="limit-modal" @click.self="showLimitModal = false">
        <div class="limit-dialog">
          <span class="material-symbols-outlined">nutrition</span>
          <h2>Calculate Limit First</h2>
          <p>Use the nutrition calculator and save your daily limit before logging food.</p>
          <FitnezButton @click="showLimitModal = false">Close</FitnezButton>
        </div>
      </div>
    </Transition>
  </WorkspaceLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.nutrition-page {
  color: #07172f;
  display: grid;
  gap: 1.25rem;
  margin-inline: auto;
  max-width: 1280px;
  width: 100%;
}

.nutrition-title {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
  padding: clamp(1.25rem, 2vw, 1.5rem);
}

.nutrition-title .eyebrow {
  color: #2563eb;
  font-size: 0.72rem;
  font-weight: 950;
  letter-spacing: 0.14em;
  margin: 0 0 0.4rem;
  text-transform: uppercase;
}

.nutrition-title h2 {
  color: #07172f;
  font-size: clamp(1.35rem, 3vw, 1.75rem);
  font-weight: 950;
  letter-spacing: 0;
  line-height: 1.1;
  margin: 0;
}

.nutrition-title span {
  color: #6f7b8d;
  display: block;
  font-size: 0.95rem;
  font-weight: 700;
  line-height: 1.55;
  margin-top: 0.45rem;
}

.nutrition-grid {
  align-items: stretch;
  display: grid;
  gap: 1.25rem;
  grid-template-columns: minmax(0, 1.35fr) minmax(340px, 0.85fr);
}

.surface-card {
  background: #ffffff;
  border: 1px solid rgba(15, 23, 42, 0.08);
  border-radius: 1.25rem;
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.calorie-card,
.calculator-panel {
  min-height: 100%;
  padding: 1.4rem;
}

.calorie-card {
  display: flex;
  flex-direction: column;
}

.calorie-content {
  align-items: center;
  display: grid;
  flex: 1;
  gap: 1.25rem;
  grid-template-columns: auto minmax(0, 1fr);
  min-width: 0;
}

.section-title-row {
  align-items: center;
  display: flex;
  justify-content: space-between;
}

.section-title-row h2,
.meals-head h2,
.calculator-title h2 {
  color: #07172f;
  font-size: 1rem;
  font-weight: 950;
  margin: 0;
}

.section-title-row span {
  background: #dfe7ff;
  border-radius: 999px;
  color: #66759a;
  font-size: 0.66rem;
  font-weight: 900;
  padding: 0.28rem 0.65rem;
}

.calorie-ring-wrap {
  display: grid;
  padding: 1.25rem 0 1rem;
  place-items: center;
}

.calorie-ring {
  border-radius: 999px;
  display: grid;
  height: 14rem;
  padding: 1rem;
  place-items: center;
  width: 14rem;
}

.calorie-ring > div {
  align-items: center;
  background: #ffffff;
  border-radius: inherit;
  box-shadow: inset 0 0 0 1px rgba(15, 23, 42, 0.04);
  display: flex;
  flex-direction: column;
  height: 100%;
  justify-content: center;
  width: 100%;
}

.calorie-ring strong {
  color: #07172f;
  font-size: 2.15rem;
  font-weight: 950;
}

.calorie-ring span {
  color: #596579;
  font-size: 0.66rem;
  font-weight: 950;
}

.calorie-stats {
  border-left: 1px solid #e5ebf2;
  display: grid;
  gap: 0.75rem;
  grid-template-columns: 1fr;
  padding-left: 1rem;
}

.calorie-stats > div {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 0.9rem;
  padding: 0.85rem;
}

.calorie-stats small,
.meal-item p,
.result-strip span {
  color: #596579;
  font-size: 0.72rem;
  font-weight: 800;
}

.calorie-stats strong,
.result-strip strong {
  color: #07172f;
  display: block;
  font-size: 1rem;
  font-weight: 950;
}

.calorie-stats .blue {
  color: #0068d8;
}

.calculator-panel {
  display: flex;
  flex-direction: column;
}

.calculator-title {
  align-items: center;
  display: flex;
  gap: 0.45rem;
  margin-bottom: 0.85rem;
}

.calculator-title span {
  color: #0068d8;
  font-size: 1rem;
}

.calculator-form {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

.calculator-form :deep(label),
.meal-add-card :deep(label),
.select-field {
  display: grid;
  gap: 0.3rem;
}

.calculator-form :deep(.form-label),
.meal-add-card :deep(.form-label),
.select-field span {
  color: #152238;
  font-size: 0.68rem;
  font-weight: 850;
}

.calculator-form :deep(input),
.meal-add-card :deep(input),
.select-field select {
  background: #ffffff !important;
  border: 1px solid #cfd9e6 !important;
  border-radius: 0.75rem !important;
  color: #152238 !important;
  font-size: 0.8rem !important;
  min-height: 2.35rem !important;
  padding: 0.48rem 0.65rem !important;
}

.wide {
  grid-column: 1 / -1;
}

.primary-action,
.result-strip button,
.meal-add-card button {
  align-items: center;
  background: #0068d8;
  border: 0;
  border-radius: 0.75rem;
  color: #ffffff;
  cursor: pointer;
  display: inline-flex;
  font-size: 0.78rem;
  font-weight: 950;
  gap: 0.4rem;
  justify-content: center;
  min-height: 2.5rem;
  padding: 0.65rem 0.85rem;
}

.primary-action span,
.meal-add-card button span {
  font-size: 1rem;
}

.result-strip {
  display: grid;
  gap: 0.55rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  margin-top: 0.85rem;
}

.result-strip div {
  background: #ffffff;
  border: 1px solid #dfe6ef;
  border-radius: 0.75rem;
  padding: 0.55rem;
}

.result-strip button {
  grid-column: 1 / -1;
}

.form-error {
  color: #dc2626;
  font-size: 0.76rem;
  font-weight: 900;
}

.save-success {
  color: #047857;
  font-size: 0.76rem;
  font-weight: 900;
  margin-top: 0.6rem;
}

.meals-card {
  grid-column: 1 / -1;
  padding: 1.35rem;
}

.meals-head {
  align-items: center;
  display: flex;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.meals-head span {
  color: #0068d8;
  font-size: 0.76rem;
  font-weight: 950;
}

.meal-list {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

.meal-item,
.meal-add-card {
  background: #f8fafc;
  border: 1px solid #dfe6ef;
  border-radius: 1rem;
  min-height: 6.5rem;
  padding: 0.85rem;
}

.meal-top {
  align-items: flex-start;
  display: flex;
  gap: 0.7rem;
  justify-content: space-between;
}

.meal-top strong {
  color: #07172f;
  font-size: 0.86rem;
  font-weight: 950;
}

.meal-top span {
  background: #e2e7ee;
  border-radius: 0.25rem;
  color: #152238;
  flex-shrink: 0;
  font-size: 0.68rem;
  font-weight: 950;
  padding: 0.18rem 0.35rem;
}

.meal-item button {
  background: transparent;
  border: 0;
  color: #be123c;
  cursor: pointer;
  font-size: 0.75rem;
  font-weight: 950;
  margin-top: 0.55rem;
  padding: 0;
}

.meal-add-card {
  border-style: dashed;
  display: grid;
  gap: 0.65rem;
}

.limit-modal {
  align-items: center;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 1rem;
  position: fixed;
  z-index: 2000;
}

.limit-dialog {
  background: #ffffff;
  border-radius: 1.25rem;
  max-width: 26rem;
  padding: 2rem;
  text-align: center;
  width: 100%;
}

.limit-dialog > span {
  color: #0068d8;
  font-size: 2.3rem;
}

.limit-dialog h2 {
  color: #07172f;
  font-size: 1.25rem;
  font-weight: 950;
  margin: 0.75rem 0 0.35rem;
}

.limit-dialog p {
  color: #596579;
  font-weight: 800;
  margin-bottom: 1.25rem;
}

@media (max-width: 1180px) {
  .nutrition-page {
    max-width: none;
  }

  .nutrition-grid {
    grid-template-columns: 1fr;
  }

  .meal-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .nutrition-grid,
  .calorie-content,
  .calculator-form,
  .result-strip,
  .meal-list,
  .calorie-stats {
    grid-template-columns: 1fr;
  }

  .surface-card {
    border-radius: 1rem;
  }

  .calorie-stats {
    border-left: 0;
    border-top: 1px solid #e5ebf2;
    padding-left: 0;
    padding-top: 1rem;
  }

  .calorie-ring {
    height: 12rem;
    width: 12rem;
  }
}
</style>
