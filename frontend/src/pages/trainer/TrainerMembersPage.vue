<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import SkeletonList from '../../components/ui/SkeletonList.vue'
import { useTrainerMemberMonitoringStore } from '../../stores/trainerMemberMonitoringStore'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

const store = useTrainerMemberMonitoringStore()
const router = useRouter()
const activeTab = ref('Overview')
const tabs = ['Overview', 'Workout Plans', 'Nutrition & Meals', 'Progress']
let searchDebounce: ReturnType<typeof setTimeout> | null = null

const searchQuery = ref('')

const filteredMembers = computed(() => {
  const q = searchQuery.value.toLowerCase()
  if (!q) return store.members
  return store.members.filter(
    (m) =>
      m.full_name?.toLowerCase().includes(q) ||
      m.email?.toLowerCase().includes(q)
  )
})

function onSearchInput(value: string) {
  searchQuery.value = value
  if (searchDebounce) clearTimeout(searchDebounce)
  searchDebounce = setTimeout(() => store.setSearch(value), 300)
}

// Dynamic computed properties based on database data
const currentPlan = computed(() => {
  if (!store.selected?.workout_plans || store.selected.workout_plans.length === 0) return null
  const plan = store.selected.workout_plans[0]
  const exercises = plan.workout_exercises || []
  const total = exercises.length
  const trackings = store.selected.workout_trackings || []
  const completed = trackings.filter(t => t.is_completed && exercises.some(ex => ex.id === t.workout_exercise_id)).length
  const pct = total > 0 ? Math.min(100, Math.round((completed / total) * 100)) : 0
  return {
    title: plan.title,
    startDate: plan.start_date,
    endDate: plan.end_date,
    status: plan.status,
    completedCount: completed,
    totalCount: total,
    percentage: pct
  }
})

const recentSessions = computed(() => {
  const trackings = store.selected?.workout_trackings || []
  return trackings.slice(0, 3).map(t => ({
    name: t.workout_exercise?.exercise?.name || 'Workout Session',
    date: t.workout_date ? new Date(t.workout_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'Recent'
  }))
})

const latestMealPlan = computed(() => {
  const plans = store.selected?.meal_plans || []
  return plans.length > 0 ? plans[0] : null
})

const nutritionStats = computed(() => {
  const targetCal = Number(store.selected?.nutrition?.target_calories || 2000)
  const assignedCal = Number(latestMealPlan.value?.total_calories || 0)
  const calPct = targetCal > 0 ? Math.min(100, Math.round((assignedCal / targetCal) * 100)) : 0
  const calOffset = 276 - (276 * calPct / 100)

  const targetProt = Number(store.selected?.nutrition?.target_protein || 150)
  const assignedProt = Number(latestMealPlan.value?.protein_grams || 0)
  const protPct = targetProt > 0 ? Math.min(100, Math.round((assignedProt / targetProt) * 100)) : 0

  const targetCarb = Number(store.selected?.nutrition?.target_carbs || 250)
  const assignedCarb = Number(latestMealPlan.value?.carbs_grams || 0)
  const carbPct = targetCarb > 0 ? Math.min(100, Math.round((assignedCarb / targetCarb) * 100)) : 0

  const targetFat = Number(store.selected?.nutrition?.target_fat || 70)
  const assignedFat = Number(latestMealPlan.value?.fat_grams || 0)
  const fatPct = targetFat > 0 ? Math.min(100, Math.round((assignedFat / targetFat) * 100)) : 0

  return {
    targetCal,
    assignedCal,
    calPct,
    calOffset,
    targetProt,
    assignedProt,
    protPct,
    targetCarb,
    assignedCarb,
    carbPct,
    targetFat,
    assignedFat,
    fatPct
  }
})

function formatDate(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleDateString('en-US', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
  })
}

function statusClass(status?: string | null) {
  if (status === 'active' || status === 'completed') return 'status-badge status-green'
  if (status === 'pending' || status === 'draft') return 'status-badge status-orange'
  if (status === 'cancelled' || status === 'inactive') return 'status-badge status-red'
  return 'status-badge status-blue'
}

function getAge(birthDate?: string | null) {
  if (!birthDate) return '-'
  const today = new Date()
  const birth = new Date(birthDate)
  let age = today.getFullYear() - birth.getFullYear()
  const monthDiff = today.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birth.getDate())) {
    age--
  }
  return age
}

async function refreshData() {
  await Promise.all([
    store.loadSummary(),
    store.loadMembers()
  ])
  if (store.selected?.member?.id) {
    await store.loadDetail(store.selected.member.id)
  }
}

onMounted(() => {
  store.loadSummary()
  store.loadMembers()
})
onUnmounted(() => {
  if (searchDebounce) clearTimeout(searchDebounce)
})
useAutoRefresh(refreshData, 8000)
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Member Fitness Monitoring"
    subtitle="Monitor workout plans, training tracking, nutrition targets, and member meal plans."
    :sidebar-items="trainerSidebarItems"
  >
    <div class="trainer-members-page">
      
      <!-- TOP METRIC CARDS -->
      <section class="metrics-grid">
        <!-- Card 1: Members -->
        <div class="metric-card">
          <div class="card-top">
            <div class="icon-wrapper bg-blue-dim text-blue">
              <span class="material-symbols-outlined">group</span>
            </div>
            <span :class="['trend-badge', (store.summary?.total_members_trend ?? 0) > 0 ? 'bg-green-dim text-green' : (store.summary?.total_members_trend ?? 0) < 0 ? 'bg-red-dim text-red' : 'bg-gray-dim text-gray']">
              <span class="material-symbols-outlined trend-arrow">{{ (store.summary?.total_members_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.total_members_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.total_members_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.total_members_trend ?? 0 }}%
            </span>
          </div>
          <div class="card-bottom">
            <h3>Total Members</h3>
            <div class="metric-value">{{ store.summary?.total_members || 0 }}</div>
          </div>
        </div>

        <!-- Card 2: Active Plans -->
        <div class="metric-card">
          <div class="card-top">
            <div class="icon-wrapper bg-purple-dim text-purple">
              <span class="material-symbols-outlined">assignment</span>
            </div>
            <span :class="['trend-badge', (store.summary?.active_workout_plans_trend ?? 0) > 0 ? 'bg-green-dim text-green' : (store.summary?.active_workout_plans_trend ?? 0) < 0 ? 'bg-red-dim text-red' : 'bg-gray-dim text-gray']">
              <span class="material-symbols-outlined trend-arrow">{{ (store.summary?.active_workout_plans_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.active_workout_plans_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.active_workout_plans_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.active_workout_plans_trend ?? 0 }}%
            </span>
          </div>
          <div class="card-bottom">
            <h3>Active Plans</h3>
            <div class="metric-value">{{ store.summary?.active_workout_plans || 0 }}</div>
          </div>
        </div>

        <!-- Card 3: Completed Logs -->
        <div class="metric-card">
          <div class="card-top">
            <div class="icon-wrapper bg-green-dim text-green">
              <span class="material-symbols-outlined">checklist</span>
            </div>
            <span :class="['trend-badge', (store.summary?.completed_trackings_trend ?? 0) > 0 ? 'bg-green-dim text-green' : (store.summary?.completed_trackings_trend ?? 0) < 0 ? 'bg-red-dim text-red' : 'bg-gray-dim text-gray']">
              <span class="material-symbols-outlined trend-arrow">{{ (store.summary?.completed_trackings_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.completed_trackings_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.completed_trackings_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.completed_trackings_trend ?? 0 }}%
            </span>
          </div>
          <div class="card-bottom">
            <h3>Completed Logs</h3>
            <div class="metric-value">{{ store.summary?.completed_trackings || 0 }}</div>
          </div>
        </div>

        <!-- Card 4: Meal Plans -->
        <div class="metric-card">
          <div class="card-top">
            <div class="icon-wrapper bg-orange-dim text-orange">
              <span class="material-symbols-outlined">restaurant</span>
            </div>
            <span :class="['trend-badge', (store.summary?.meal_plans_trend ?? 0) > 0 ? 'bg-green-dim text-green' : (store.summary?.meal_plans_trend ?? 0) < 0 ? 'bg-red-dim text-red' : 'bg-gray-dim text-gray']">
              <span class="material-symbols-outlined trend-arrow">{{ (store.summary?.meal_plans_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.meal_plans_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.meal_plans_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.meal_plans_trend ?? 0 }}%
            </span>
          </div>
          <div class="card-bottom">
            <h3>Meal Plans</h3>
            <div class="metric-value">{{ store.summary?.meal_plans || 0 }}</div>
          </div>
        </div>
      </section>

      <!-- MAIN SPLIT WORKSPACE -->
      <div class="split-layout">
        
        <!-- LEFT COLUMN: MEMBER LIST -->
        <div class="left-panel">
          <div class="panel-header">
            <h2>Choose Member</h2>
            <div class="search-input-wrapper">
              <span class="material-symbols-outlined search-icon">search</span>
              <input
                type="text"
                class="search-input"
                style="padding-left: 2.75rem !important;"
                placeholder="Search members..."
                @input="onSearchInput(($event.target as HTMLInputElement).value)"
              />
            </div>
          </div>

          <div class="member-list-scroller">
            <SkeletonList v-if="store.loadingMembers && !store.members.length" :rows="8" :avatar="true" />

            <button
              v-for="member in filteredMembers"
              :key="member.id"
              type="button"
              :class="['member-row-btn', { 'row-btn-active': store.selected?.member.id === member.id }]"
              @click="store.loadDetail(member.id)"
            >
              <div class="avatar-ring">
                <div class="avatar-letter">{{ member.full_name?.charAt(0) || 'M' }}</div>
                <span class="online-indicator"></span>
              </div>
              <div class="member-row-copy">
                <span class="member-name">{{ member.full_name }}</span>
                <span class="member-sub">{{ member.workout_plans_count || 0 }} plans - {{ member.workout_trackings_count || 0 }} logs</span>
              </div>
              <span class="material-symbols-outlined chevron-icon">chevron_right</span>
            </button>

            <div v-if="!filteredMembers.length && !store.loadingMembers" class="empty-state-list">
              <p>No members found.</p>
              <span>Try a different search term.</span>
            </div>
          </div>

          <!-- PAGER BAR -->
          <div class="pager-footer">
            <span class="pager-text">Page {{ store.page }} of {{ store.lastPage }}</span>
            <div class="pager-buttons">
              <button
                class="pager-btn"
                :disabled="store.page <= 1"
                @click="store.previousPage"
              >
                Previous
              </button>
              <button
                class="pager-btn"
                :disabled="store.page >= store.lastPage"
                @click="store.nextPage"
              >
                Next
              </button>
            </div>
          </div>
        </div>

        <!-- RIGHT COLUMN: MEMBER DETAILS -->
        <div class="right-panel">
          <div v-if="store.loadingDetail" class="detail-loading-state">
            <SkeletonList :rows="10" />
            <p>Loading member detail records...</p>
          </div>

          <div v-else-if="store.selected" class="detail-content">
            
            <!-- PROFILE HEADER -->
            <div class="detail-hero-card">
              <div class="avatar-ring-large">
                <div class="avatar-letter-large">{{ store.selected.member.full_name?.charAt(0) || 'M' }}</div>
                <span class="online-indicator-large"></span>
              </div>
              <div class="hero-text-block">
                <h2>{{ store.selected.member.full_name }}</h2>
                <div class="hero-meta-row">
                  <span class="meta-item"><span class="material-symbols-outlined">mail</span> {{ store.selected.member.email }}</span>
                  <span class="meta-dot" v-if="store.selected.member.phone">-</span>
                  <span class="meta-item" v-if="store.selected.member.phone"><span class="material-symbols-outlined">call</span> {{ store.selected.member.phone }}</span>
                </div>
                <div class="hero-chip-row">
                  <span class="info-chip">Age: {{ getAge(store.selected.member.birth_date) }}</span>
                </div>
              </div>
              <div class="hero-actions">
                <button type="button" class="btn-secondary" @click="router.push('/trainer/chat?userId=' + store.selected.member.id)">Message</button>
              </div>
            </div>

            <!-- TAB NAVIGATION -->
            <div class="tabs-nav-bar">
              <button
                v-for="tab in tabs"
                :key="tab"
                :class="['tab-link-btn', { 'tab-link-active': activeTab === tab }]"
                @click="activeTab = tab"
              >
                {{ tab }}
              </button>
            </div>

            <!-- TAB PAGES -->
            <div class="tab-page-content">
              
              <!-- TAB 1: OVERVIEW -->
              <div v-if="activeTab === 'Overview'" class="overview-grid">
                <!-- Workout Phase Card -->
                <div class="inner-detail-card">
                  <div class="inner-card-head">
                    <h3>Current Workout Plan</h3>
                    <button class="text-btn" @click="activeTab = 'Workout Plans'">View All</button>
                  </div>
                  
                  <div v-if="currentPlan" class="progress-block">
                    <div class="progress-meta">
                      <div>
                        <h4>{{ currentPlan.title }}</h4>
                        <p v-if="currentPlan.startDate">{{ formatDate(currentPlan.startDate) }} - {{ formatDate(currentPlan.endDate) }}</p>
                      </div>
                      <span :class="statusClass(currentPlan.status)">{{ currentPlan.status || 'Active' }}</span>
                    </div>
                    <div class="progress-bar-container">
                      <div class="progress-fill" :style="{ width: currentPlan.percentage + '%' }"></div>
                    </div>
                    <p class="progress-percentage-label">{{ currentPlan.percentage }}% Completed ({{ currentPlan.completedCount }}/{{ currentPlan.totalCount }} exercises)</p>
                  </div>
                  <div v-else class="empty-placeholder">
                    <p>No active workout plan</p>
                  </div>

                  <div class="recent-sessions-block">
                    <h5>Recent Sessions</h5>
                    <div v-for="session in recentSessions" :key="session.name" class="session-log-row">
                      <span class="session-label">
                        <span class="material-symbols-outlined check-icon">check_circle</span>
                        {{ session.name }}
                      </span>
                      <span class="session-time">{{ session.date }}</span>
                    </div>
                    <div v-if="recentSessions.length === 0" class="empty-placeholder mt-2">
                      <p>No sessions recorded yet</p>
                    </div>
                  </div>
                </div>

                <!-- Nutrition Targets Card -->
                <div class="inner-detail-card">
                  <div class="inner-card-head">
                    <h3>Nutrition Goals</h3>
                    <button class="text-btn" @click="activeTab = 'Nutrition & Meals'">View Detail</button>
                  </div>

                  <div class="nutrition-circular-display">
                    <div class="circle-container">
                      <svg class="circle-svg" viewBox="0 0 100 100">
                        <circle cx="50" cy="50" r="44" stroke="#1e242c" stroke-width="8" fill="transparent"></circle>
                        <circle cx="50" cy="50" r="44" stroke="#3b82f6" stroke-width="8" :stroke-dasharray="276" :stroke-dashoffset="nutritionStats.calOffset" fill="transparent" stroke-linecap="round"></circle>
                      </svg>
                      <div class="circle-center-text">
                        <strong>{{ nutritionStats.targetCal }}</strong>
                        <span>kcal / day target</span>
                        <small class="text-xs text-gray-400 mt-1 block" style="font-size: 0.7rem;">{{ nutritionStats.assignedCal }} kcal assigned</small>
                      </div>
                    </div>
                  </div>

                  <div class="macros-flex-grid">
                    <div class="macro-capsule">
                      <span class="macro-label">Protein (Target: {{ nutritionStats.targetProt }}g)</span>
                      <strong class="macro-amount">{{ nutritionStats.assignedProt }}g assigned</strong>
                      <div class="macro-line-bg"><div class="macro-line-fill bg-blue" :style="{ width: nutritionStats.protPct + '%' }"></div></div>
                    </div>
                    <div class="macro-capsule">
                      <span class="macro-label">Carbs (Target: {{ nutritionStats.targetCarb }}g)</span>
                      <strong class="macro-amount">{{ nutritionStats.assignedCarb }}g assigned</strong>
                      <div class="macro-line-bg"><div class="macro-line-fill bg-orange" :style="{ width: nutritionStats.carbPct + '%' }"></div></div>
                    </div>
                    <div class="macro-capsule">
                      <span class="macro-label">Fats (Target: {{ nutritionStats.targetFat }}g)</span>
                      <strong class="macro-amount">{{ nutritionStats.assignedFat }}g assigned</strong>
                      <div class="macro-line-bg"><div class="macro-line-fill bg-purple" :style="{ width: nutritionStats.fatPct + '%' }"></div></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- TAB 2: WORKOUT PLANS -->
              <div v-if="activeTab === 'Workout Plans'" class="workout-plans-view">
                <div class="inner-detail-card">
                  <div class="inner-card-head mb-4">
                    <h3>All Assigned Workout Plans</h3>
                  </div>

                  <div class="table-wrapper">
                    <table class="premium-table">
                      <thead>
                        <tr>
                          <th>Plan Title</th>
                          <th>Status</th>
                          <th>Period</th>
                          <th>Exercises</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="plan in store.selected.workout_plans" :key="plan.id">
                          <td>
                            <strong class="text-white">{{ plan.title }}</strong>
                            <p class="text-dim text-xs mt-1">{{ plan.description || 'No description' }}</p>
                          </td>
                          <td><span :class="statusClass(plan.status)">{{ plan.status || 'not set' }}</span></td>
                          <td>{{ formatDate(plan.start_date) }} - {{ formatDate(plan.end_date) }}</td>
                          <td>
                            <div v-for="item in plan.workout_exercises" :key="item.id" class="exercise-mini-log">
                              <strong>{{ item.exercise?.name || 'Exercise' }}</strong>
                              <span>Day {{ item.day_of_week }}, {{ item.sets }} x {{ item.reps }} reps</span>
                            </div>
                          </td>
                        </tr>
                        <tr v-if="!store.selected.workout_plans.length">
                          <td colspan="4" class="empty-cell">No workout plans assigned yet.</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- TAB 3: NUTRITION & MEALS -->
              <div v-if="activeTab === 'Nutrition & Meals'" class="nutrition-meals-view">
                <div class="inner-detail-card mb-6">
                  <div class="inner-card-head mb-4">
                    <h3>Nutrition Calculator Targets</h3>
                  </div>

                  <div v-if="store.selected.nutrition" class="calculator-values-grid">
                    <div class="calc-tile">
                      <span>BMR (Basal Metabolic Rate)</span>
                      <strong>{{ store.selected.nutrition.bmr || '-' }} kcal</strong>
                    </div>
                    <div class="calc-tile">
                      <span>TDEE (Daily Energy Estimate)</span>
                      <strong>{{ store.selected.nutrition.tdee || '-' }} kcal</strong>
                    </div>
                    <div class="calc-tile highlight-blue">
                      <span>Target Calorie Intake</span>
                      <strong>{{ store.selected.nutrition.target_calories || '-' }} kcal</strong>
                    </div>
                    <div class="calc-tile">
                      <span>Macros Ratio</span>
                      <small>P: {{ store.selected.nutrition.target_protein }}g - C: {{ store.selected.nutrition.target_carbs }}g - F: {{ store.selected.nutrition.target_fat }}g</small>
                    </div>
                  </div>
                  <div v-else class="empty-placeholder">
                    <p>No nutrition calculation available for this member.</p>
                  </div>
                </div>

                <div class="inner-detail-card">
                  <div class="inner-card-head mb-4">
                    <h3>Meal Plans</h3>
                  </div>

                  <div class="table-wrapper">
                    <table class="premium-table">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Meal Plan Title</th>
                          <th>Macro Breakdowns</th>
                          <th>Meals Assigned</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="plan in store.selected.meal_plans" :key="plan.id">
                          <td>{{ formatDate(plan.plan_date) }}</td>
                          <td><strong class="text-white">{{ plan.title }}</strong></td>
                          <td>{{ plan.total_calories || 0 }} kcal - P {{ plan.protein_grams || 0 }}g - C {{ plan.carbs_grams || 0 }}g - F {{ plan.fat_grams || 0 }}g</td>
                          <td>
                            <div v-for="meal in plan.meals" :key="meal.id" class="meal-mini-log">
                              <span class="meal-type">{{ meal.meal_type }}:</span>
                              <span class="food-name">{{ meal.food_name }}</span>
                            </div>
                          </td>
                        </tr>
                        <tr v-if="!store.selected.meal_plans.length">
                          <td colspan="4" class="empty-cell">No meal plans assigned yet.</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

              <!-- TAB 4: PROGRESS -->
              <div v-if="activeTab === 'Progress'" class="progress-view">
                <div class="inner-detail-card">
                  <div class="inner-card-head mb-4">
                    <h3>Recent Training Tracking Logs</h3>
                  </div>

                  <div class="table-wrapper">
                    <table class="premium-table">
                      <thead>
                        <tr>
                          <th>Date</th>
                          <th>Exercise Name</th>
                          <th>Actual Performance</th>
                          <th>Status</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="tracking in store.selected.workout_trackings" :key="tracking.id">
                          <td>{{ formatDate(tracking.workout_date) }}</td>
                          <td>{{ tracking.workout_exercise?.exercise?.name || 'Exercise' }}</td>
                          <td>{{ tracking.actual_sets || 0 }} sets, {{ tracking.actual_reps || 0 }} reps, {{ tracking.actual_weight_kg || 0 }} kg</td>
                          <td>
                            <span :class="['status-badge', tracking.is_completed ? 'status-green' : 'status-orange']">
                              {{ tracking.is_completed ? 'Completed' : 'Incomplete' }}
                            </span>
                          </td>
                        </tr>
                        <tr v-if="!store.selected.workout_trackings.length">
                          <td colspan="4" class="empty-cell">No training logs recorded yet.</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <div v-else class="detail-empty-state">
            <span class="material-symbols-outlined large-empty-icon">account_box</span>
            <h3>Choose a member</h3>
            <p>Member fitness details and monitoring charts will appear here after a member is selected.</p>
          </div>
        </div>

      </div>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.material-symbols-outlined {
  direction: ltr;
  display: inline-block;
  flex: 0 0 auto;
  font-family: 'Material Symbols Outlined';
  font-feature-settings: 'liga';
  font-size: 1.25rem;
  font-style: normal;
  font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
  font-weight: normal;
  letter-spacing: normal;
  line-height: 1;
  text-transform: none;
  white-space: nowrap;
  width: 1em;
  word-wrap: normal;
  -webkit-font-feature-settings: 'liga';
  -webkit-font-smoothing: antialiased;
}

.trainer-members-page {
  color: #e3e6eb;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* METRIC CARDS */
.metrics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.25rem;
}

.metric-card {
  background-color: #181c22;
  border: 1px solid #353940;
  border-radius: 1rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 8.5rem;
  box-shadow: 0 4px 6px rgba(0,0,0,0.15);
  transition: all 0.2s ease;
}

.metric-card:hover {
  transform: translateY(-2px);
  border-color: #3b82f6;
}

.card-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 0.75rem;
}

.icon-wrapper .material-symbols-outlined {
  font-size: 1.25rem;
}

.bg-blue-dim { background-color: rgba(59, 130, 246, 0.1); }
.text-blue { color: #3b82f6; }

.bg-purple-dim { background-color: rgba(139, 92, 246, 0.1); }
.text-purple { color: #8b5cf6; }

.bg-green-dim { background-color: rgba(16, 185, 129, 0.1); }
.text-green { color: #10b981; }

.bg-red-dim { background-color: rgba(239, 68, 68, 0.1); }
.text-red { color: #ef4444; }

.bg-gray-dim { background-color: rgba(148, 163, 184, 0.1); }
.text-gray { color: #94a3b8; }

.bg-orange-dim { background-color: rgba(245, 158, 11, 0.1); }
.text-orange { color: #f59e0b; }

.trend-badge {
  display: inline-flex;
  align-items: center;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 0.5rem;
  gap: 0.25rem;
}

.trend-arrow {
  font-size: 0.85rem;
}

.card-bottom h3 {
  color: #848e9c;
  font-size: 0.8rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.25rem 0;
}

.metric-value {
  font-size: 1.85rem;
  font-weight: 800;
  color: #ffffff;
  line-height: 1.1;
}

/* SPLIT LAYOUT */
.split-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .split-layout {
    grid-template-columns: 340px 1fr;
  }
}

/* LEFT PANEL (MEMBER LIST) */
.left-panel {
  background-color: #0a0e14;
  border: 1px solid #353940;
  border-radius: 1rem;
  display: flex;
  flex-direction: column;
  height: 600px;
  overflow: hidden;
}

.panel-header {
  padding: 1.25rem;
  border-bottom: 1px solid #353940;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.panel-header h2 {
  color: #ffffff;
  font-size: 1.15rem;
  font-weight: 800;
  margin: 0;
}

.search-input-wrapper {
  position: relative;
  width: 100%;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: #848e9c;
  font-size: 1.15rem;
}

.search-input {
  width: 100%;
  background-color: #1e242c;
  border: 1px solid #353940;
  border-radius: 0.5rem;
  color: #ffffff;
  padding: 0.5rem 0.75rem 0.5rem 2.25rem !important;
  font-size: 0.85rem;
  outline: none;
}

.search-input:focus {
  border-color: #3b82f6 !important;
  box-shadow: none !important;
}

.member-list-scroller {
  flex: 1;
  overflow-y: auto;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

/* SCROLLBAR HIDE */
.member-list-scroller::-webkit-scrollbar { display: none; }
.member-list-scroller { -ms-overflow-style: none; scrollbar-width: none; }

.member-row-btn {
  display: flex;
  align-items: center;
  width: 100%;
  background: transparent;
  border: 1px solid transparent;
  border-radius: 0.75rem;
  padding: 0.75rem;
  text-align: left;
  transition: all 0.15s ease;
  cursor: pointer;
  gap: 0.75rem;
}

.member-row-btn:hover {
  background-color: #1e242c;
}

.row-btn-active {
  background-color: #1e242c !important;
  border-color: rgba(59, 130, 246, 0.3) !important;
}

.avatar-ring {
  position: relative;
}

.avatar-letter {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 999px;
  background-color: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 0.95rem;
}

.online-indicator {
  position: absolute;
  bottom: 0;
  right: 0;
  width: 0.65rem;
  height: 0.65rem;
  background-color: #10b981;
  border: 2px solid #1e242c;
  border-radius: 999px;
}

.member-row-copy {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.member-name {
  color: #ffffff;
  font-weight: 700;
  font-size: 0.88rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.member-sub {
  color: #848e9c;
  font-size: 0.75rem;
}

.chevron-icon {
  font-size: 1rem;
  color: #848e9c;
}

.empty-state-list {
  text-align: center;
  padding: 2rem 1rem;
  color: #848e9c;
}

.empty-state-list p {
  font-weight: 700;
  margin: 0;
  font-size: 0.85rem;
}

.empty-state-list span {
  font-size: 0.75rem;
}

.pager-footer {
  padding: 1rem;
  border-top: 1px solid #353940;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.pager-text {
  font-size: 0.8rem;
  color: #848e9c;
  font-weight: 700;
}

.pager-buttons {
  display: flex;
  gap: 0.5rem;
}

.pager-btn {
  background-color: #1e242c;
  border: 1px solid #353940;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.75rem;
  border-radius: 0.5rem;
  padding: 0.35rem 0.65rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.pager-btn:hover:not(:disabled) {
  background-color: #353940;
}

.pager-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

/* RIGHT PANEL (MEMBER DETAIL) */
.right-panel {
  background-color: #0a0e14;
  border: 1px solid #353940;
  border-radius: 1rem;
  min-height: 600px;
  display: flex;
  flex-direction: column;
}

.detail-loading-state,
.detail-empty-state {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  text-align: center;
  color: #848e9c;
}

.large-empty-icon {
  font-size: 4rem;
  color: #353940;
  margin-bottom: 1rem;
}

.detail-empty-state h3 {
  color: #ffffff;
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0 0 0.5rem 0;
}

.detail-empty-state p {
  font-size: 0.85rem;
  max-width: 20rem;
  margin: 0;
  line-height: 1.4;
}

.detail-content {
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* DETAIL HERO */
.detail-hero-card {
  background-color: #181c22;
  border: 1px solid #353940;
  border-radius: 1rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 1rem;
}

@media (min-width: 768px) {
  .detail-hero-card {
    flex-direction: row;
    align-items: flex-start;
    text-align: left;
    gap: 1.5rem;
  }
}

.avatar-ring-large {
  position: relative;
  flex-shrink: 0;
}

.avatar-letter-large {
  width: 4.5rem;
  height: 4.5rem;
  border-radius: 999px;
  background-color: rgba(59, 130, 246, 0.2);
  color: #3b82f6;
  border: 1px solid rgba(59, 130, 246, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 800;
  font-size: 1.75rem;
}

.online-indicator-large {
  position: absolute;
  bottom: 0.25rem;
  right: 0.25rem;
  width: 0.85rem;
  height: 0.85rem;
  background-color: #10b981;
  border: 2px solid #181c22;
  border-radius: 999px;
}

.hero-text-block {
  flex: 1;
  min-width: 0;
}

.hero-text-block h2 {
  color: #ffffff;
  font-size: 1.5rem;
  font-weight: 800;
  margin: 0 0 0.25rem 0;
}

.hero-meta-row {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-wrap: wrap;
  gap: 0.5rem;
  color: #848e9c;
  font-size: 0.8rem;
  margin-bottom: 0.75rem;
}

@media (min-width: 768px) {
  .hero-meta-row {
    justify-content: flex-start;
  }
}

.meta-item {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
}

.meta-item .material-symbols-outlined {
  font-size: 0.95rem;
}

.meta-dot {
  font-weight: 900;
}

.hero-chip-row {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.5rem;
}

@media (min-width: 768px) {
  .hero-chip-row {
    justify-content: flex-start;
  }
}

.info-chip {
  background-color: #1e242c;
  border: 1px solid #353940;
  border-radius: 0.35rem;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  color: #e3e6eb;
}

.hero-actions {
  display: flex;
  gap: 0.5rem;
  width: 100%;
}

@media (min-width: 768px) {
  .hero-actions {
    width: auto;
    align-self: flex-start;
  }
}

.btn-primary {
  flex: 1;
  background-color: #3b82f6;
  color: #ffffff;
  border: none;
  font-weight: 800;
  font-size: 0.8rem;
  border-radius: 0.5rem;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-primary:hover {
  background-color: #2563eb;
}

.btn-secondary {
  flex: 1;
  background-color: #1e242c;
  border: 1px solid #353940;
  color: #ffffff;
  font-weight: 800;
  font-size: 0.8rem;
  border-radius: 0.5rem;
  padding: 0.5rem 1rem;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-secondary:hover {
  background-color: #353940;
}

/* TABS NAV */
.tabs-nav-bar {
  display: flex;
  border-bottom: 1px solid #353940;
  overflow-x: auto;
  gap: 1.5rem;
}

.tabs-nav-bar::-webkit-scrollbar { display: none; }
.tabs-nav-bar { -ms-overflow-style: none; scrollbar-width: none; }

.tab-link-btn {
  background: transparent;
  border: none;
  border-bottom: 2px solid transparent;
  color: #848e9c;
  font-weight: 700;
  font-size: 0.85rem;
  padding: 0.75rem 0.25rem;
  cursor: pointer;
  transition: all 0.15s ease;
  white-space: nowrap;
}

.tab-link-btn:hover {
  color: #ffffff;
}

.tab-link-active {
  color: #3b82f6 !important;
  border-bottom-color: #3b82f6 !important;
}

/* TAB PAGE CONTENT */
.tab-page-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

/* OVERVIEW TAB STYLE */
.overview-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.25rem;
}

@media (min-width: 768px) {
  .overview-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.inner-detail-card {
  background-color: #181c22;
  border: 1px solid #353940;
  border-radius: 1rem;
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
}

.inner-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.inner-card-head h3 {
  color: #ffffff;
  font-size: 0.85rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
}

.text-btn {
  background: transparent;
  border: none;
  color: #3b82f6;
  font-weight: 700;
  font-size: 0.8rem;
  cursor: pointer;
}

.text-btn:hover {
  text-decoration: underline;
}

.progress-block {
  background-color: #1e242c;
  border: 1px solid #353940;
  border-radius: 0.75rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.progress-meta {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.progress-meta h4 {
  color: #ffffff;
  font-size: 0.88rem;
  font-weight: 700;
  margin: 0;
}

.progress-meta p {
  color: #848e9c;
  font-size: 0.75rem;
  margin: 0.15rem 0 0 0;
}

.status-badge {
  font-size: 0.65rem;
  font-weight: 900;
  text-transform: uppercase;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  letter-spacing: 0.04em;
  display: inline-flex;
}

.status-green { background-color: rgba(16, 185, 129, 0.1); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.2); }
.status-orange { background-color: rgba(245, 158, 11, 0.1); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.2); }
.status-red { background-color: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
.status-blue { background-color: rgba(59, 130, 246, 0.1); color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.2); }

.progress-bar-container {
  width: 100%;
  height: 0.4rem;
  background-color: #0a0e14;
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background-color: #3b82f6;
  border-radius: 999px;
}

.progress-percentage-label {
  color: #848e9c;
  font-size: 0.75rem;
  text-align: right;
  margin: 0;
  font-weight: 700;
}

.recent-sessions-block {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.recent-sessions-block h5 {
  color: #848e9c;
  font-size: 0.75rem;
  font-weight: 800;
  text-transform: uppercase;
  margin: 0;
}

.session-log-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.85rem;
}

.session-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: #e3e6eb;
}

.check-icon {
  color: #10b981;
  font-size: 0.95rem;
}

.session-time {
  color: #848e9c;
  font-size: 0.75rem;
}

/* NUTRITION IN TAB OVERVIEW */
.nutrition-circular-display {
  display: flex;
  justify-content: center;
  margin: 1rem 0 1.5rem 0;
}

.circle-container {
  position: relative;
  width: 110px;
  height: 110px;
}

.circle-svg {
  width: 100%;
  height: 100%;
}

.circle-center-text {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  text-align: center;
}

.circle-center-text strong {
  font-size: 1.35rem;
  font-weight: 800;
  color: #ffffff;
}

.circle-center-text span {
  font-size: 0.65rem;
  color: #848e9c;
  text-transform: uppercase;
}

.macros-flex-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0.5rem;
}

.macro-capsule {
  background-color: #1e242c;
  border: 1px solid #353940;
  border-radius: 0.5rem;
  padding: 0.5rem;
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.macro-label {
  font-size: 0.75rem;
  color: #848e9c;
}

.macro-amount {
  font-size: 0.88rem;
  color: #ffffff;
  font-weight: 800;
}

.macro-line-bg {
  width: 100%;
  height: 0.25rem;
  background-color: #0a0e14;
  border-radius: 999px;
  margin-top: 0.25rem;
  overflow: hidden;
}

.macro-line-fill {
  height: 100%;
  border-radius: 999px;
}

.bg-blue { background-color: #3b82f6; }
.bg-orange { background-color: #f59e0b; }
.bg-purple { background-color: #8b5cf6; }

/* TABLES COMMON DESIGN */
.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.premium-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
  text-align: left;
}

.premium-table th {
  background-color: #1e242c;
  color: #848e9c;
  font-weight: 700;
  text-transform: uppercase;
  font-size: 0.7rem;
  letter-spacing: 0.05em;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid #353940;
}

.premium-table td {
  padding: 1rem;
  border-bottom: 1px solid #353940;
  color: #c6cad1;
  vertical-align: middle;
}

.premium-table tbody tr:hover {
  background-color: rgba(255, 255, 255, 0.02);
}

.empty-cell {
  text-align: center;
  color: #848e9c;
  font-weight: 700;
  padding: 3rem 0;
}

.exercise-mini-log,
.meal-mini-log {
  display: flex;
  flex-direction: column;
  margin-bottom: 0.35rem;
}

.exercise-mini-log strong {
  color: #ffffff;
}

.exercise-mini-log span,
.meal-mini-log .food-name {
  color: #848e9c;
  font-size: 0.75rem;
}

.meal-mini-log .meal-type {
  color: #3b82f6;
  font-weight: 700;
  font-size: 0.75rem;
}

.empty-placeholder {
  padding: 2rem;
  text-align: center;
  color: #848e9c;
  background-color: #1e242c;
  border-radius: 0.5rem;
  border: 1px dashed #353940;
  margin-bottom: 1rem;
}

/* NUTRITION TAB VALUES */
.calculator-values-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.75rem;
}

.calc-tile {
  background-color: #1e242c;
  border: 1px solid #353940;
  border-radius: 0.75rem;
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.calc-tile span {
  font-size: 0.75rem;
  color: #848e9c;
}

.calc-tile strong {
  font-size: 1.25rem;
  color: #ffffff;
  font-weight: 800;
}

.calc-tile small {
  font-size: 0.75rem;
  color: #e3e6eb;
}

.highlight-blue {
  background-color: rgba(59, 130, 246, 0.15) !important;
  border-color: #3b82f6 !important;
}

.highlight-blue strong {
  color: #3b82f6 !important;
}

.text-white { color: #ffffff; }
.text-dim { color: #848e9c; }
.mt-1 { margin-top: 0.25rem; }
.mb-4 { margin-bottom: 1rem; }
.mb-6 { margin-bottom: 1.5rem; }

/* Light workspace override to match the rest of the trainer/admin pages. */
.trainer-members-page {
  color: #0f172a;
}

.metric-card,
.left-panel,
.right-panel,
.detail-hero-card,
.inner-detail-card {
  background: #ffffff;
  border-color: rgba(15, 23, 42, 0.08);
  box-shadow: 0 18px 45px rgba(15, 23, 42, 0.06);
}

.left-panel,
.right-panel {
  min-height: 600px;
}

.panel-header,
.pager-footer,
.tabs-nav-bar,
.premium-table th,
.premium-table td {
  border-color: rgba(15, 23, 42, 0.08);
}

.card-bottom h3,
.member-sub,
.chevron-icon,
.pager-text,
.hero-meta-row,
.progress-meta p,
.recent-sessions-block h5,
.session-time,
.circle-center-text span,
.macro-label,
.premium-table th,
.premium-table td,
.exercise-mini-log span,
.meal-mini-log .food-name,
.calc-tile span,
.empty-state-list,
.detail-loading-state,
.detail-empty-state,
.detail-empty-state p,
.progress-percentage-label,
.text-dim {
  color: #64748b;
}

.metric-value,
.panel-header h2,
.member-name,
.detail-empty-state h3,
.hero-text-block h2,
.progress-meta h4,
.inner-card-head h3,
.circle-center-text strong,
.macro-amount,
.exercise-mini-log strong,
.calc-tile strong,
.text-white {
  color: #0f172a;
}

.search-input,
.pager-btn,
.btn-secondary,
.progress-block,
.macro-capsule,
.calc-tile,
.info-chip,
.empty-placeholder {
  background: #f8fafc;
  border-color: rgba(15, 23, 42, 0.08);
  color: #0f172a;
}

.search-input {
  color: #0f172a;
}

.search-input::placeholder {
  color: #94a3b8;
}

.member-row-btn:hover,
.row-btn-active {
  background: #f8fafc !important;
  border-color: rgba(37, 99, 235, 0.2) !important;
}

.avatar-letter,
.avatar-letter-large {
  background: #dbeafe;
  border-color: #bfdbfe;
  color: #1d4ed8;
}

.online-indicator,
.online-indicator-large {
  border-color: #ffffff;
}

.large-empty-icon {
  color: #cbd5e1;
}

.tab-link-btn:hover {
  color: #0f172a;
}

.tab-link-active {
  color: #2563eb !important;
  border-bottom-color: #2563eb !important;
}

.progress-bar-container,
.macro-line-bg,
.premium-table th {
  background: #f1f5f9;
}

.premium-table tbody tr:hover {
  background: #f8fafc;
}

.highlight-blue {
  background: #eff6ff !important;
  border-color: #bfdbfe !important;
}

.highlight-blue strong,
.meal-mini-log .meal-type,
.text-btn {
  color: #2563eb !important;
}
</style>
