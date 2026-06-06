<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import ExcelImportModal from '../../components/ExcelImportModal.vue'
import { http } from '../../api/http'
import { mealPlanApi } from '../../api/mealPlanApi'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

interface MemberNutrition {
  id: number
  name: string
  email: string
  daily_limit: number
  total_calories: number
}

const members = ref<MemberNutrition[]>([])
const error = ref('')
const { loading, run, shimmerStyle } = useDeferredLoading()

async function loadMembers() {
  error.value = ''
  try {
    const response = await mealPlanApi.getAllMemberNutrition()
    members.value = (response as any).members ?? []
  } catch {
    error.value = 'Failed to load nutrition monitoring data.'
  }
}

function getPercentage(total: number, limit: number) {
  if (limit <= 0) return 0
  return Math.min(100, Math.round((total / limit) * 100))
}

function getStatus(total: number, limit: number) {
  if (limit <= 0) return { label: 'No limit set', color: '#64748b' }
  const percentage = (total / limit) * 100
  if (percentage >= 100) return { label: 'Exceeded limit', color: '#dc2626' }
  if (percentage >= 80) return { label: 'Near limit', color: '#d97706' }
  return { label: 'Normal', color: '#059669' }
}

onMounted(() => run(loadMembers))
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Nutrition Monitoring"
    subtitle="Monitor daily calorie consumption of all members."
    :sidebar-items="adminSidebarItems"
  >
    <div v-if="loading && !members.length" :style="shimmerStyle">
      <SkeletonStatGrid :count="3" />
      <div style="margin-top: 1.25rem"><SkeletonTable :columns="5" :rows="8" /></div>
    </div>

    <div v-else class="nutrition-page">
      <div class="page-actions">
        <button
          class="button button-ghost"
          type="button"
          @click="http.downloadBlob('/admin/export/nutrition-monitoring', 'nutrition-monitoring.xlsx')"
        >
          Export Excel
        </button>
        <ExcelImportModal import-type="workouts" label="Import Excel" />
      </div>

      <div class="feature-grid">
        <FitnezCard>
          <p class="eyebrow">Total Members</p>
          <h2 class="title-md">{{ members.length }}</h2>
          <p class="text-muted">Members with active meal plans</p>
        </FitnezCard>
        <FitnezCard>
          <p class="eyebrow">Exceeded Limit</p>
          <h2 class="title-md" style="color: #dc2626;">
            {{ members.filter(member => member.total_calories >= member.daily_limit && member.daily_limit > 0).length }}
          </h2>
          <p class="text-muted">Members exceeding calorie limit today</p>
        </FitnezCard>
        <FitnezCard>
          <p class="eyebrow">No Limit Set</p>
          <h2 class="title-md" style="color: #d97706;">
            {{ members.filter(member => member.daily_limit <= 0).length }}
          </h2>
          <p class="text-muted">Members who have not set a daily limit</p>
        </FitnezCard>
      </div>

      <FitnezCard>
        <h2 class="title-md">Member Calorie Consumption Data</h2>

        <div v-if="error" class="alert alert-error" style="margin-top: 1rem;">
          {{ error }}
        </div>

        <div v-else-if="members.length === 0" class="empty-state">
          No members have set up a meal plan yet.
        </div>

        <div v-else class="responsive-table">
          <table class="data-table">
            <thead>
              <tr>
                <th>Member</th>
                <th>Daily Limit</th>
                <th>Consumed</th>
                <th>Progress</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="member in members" :key="member.id">
                <td>
                  <p class="member-name">{{ member.name }}</p>
                  <p class="member-email">{{ member.email }}</p>
                </td>
                <td>{{ member.daily_limit > 0 ? `${member.daily_limit.toLocaleString('en-US')} kcal` : '-' }}</td>
                <td>{{ member.total_calories.toLocaleString('en-US') }} kcal</td>
                <td style="min-width: 150px;">
                  <div class="progress-track">
                    <div
                      class="progress-fill"
                      :style="{
                        width: `${getPercentage(member.total_calories, member.daily_limit)}%`,
                        background: getPercentage(member.total_calories, member.daily_limit) >= 100 ? '#dc2626' : '#f97316',
                      }"
                    />
                  </div>
                  <p class="progress-label">{{ getPercentage(member.total_calories, member.daily_limit) }}%</p>
                </td>
                <td>
                  <span class="status-label" :style="{ color: getStatus(member.total_calories, member.daily_limit).color }">
                    {{ getStatus(member.total_calories, member.daily_limit).label }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.nutrition-page {
  display: grid;
  gap: 1.25rem;
}

.page-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: flex-end;
}

.page-actions .button {
  font-size: 0.85rem;
}

.responsive-table {
  margin-top: 1rem;
  overflow-x: auto;
}

.data-table {
  border-collapse: collapse;
  width: 100%;
}

.data-table th,
.data-table td {
  border-bottom: 1px solid #e2e8f0;
  padding: 0.85rem;
  text-align: left;
  vertical-align: middle;
}

.data-table th {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.member-name {
  color: #0f172a;
  font-weight: 800;
  margin: 0;
}

.member-email {
  color: #94a3b8;
  font-size: 0.8rem;
  margin: 0.15rem 0 0;
}

.progress-track {
  background: #e2e8f0;
  border-radius: 999px;
  height: 0.5rem;
  overflow: hidden;
}

.progress-fill {
  border-radius: 999px;
  height: 100%;
  transition: width 0.4s ease;
}

.progress-label {
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 700;
  margin: 0.35rem 0 0;
}

.status-label {
  font-size: 0.85rem;
  font-weight: 800;
}

.empty-state {
  color: #64748b;
  font-weight: 700;
  margin-top: 1rem;
}
</style>
