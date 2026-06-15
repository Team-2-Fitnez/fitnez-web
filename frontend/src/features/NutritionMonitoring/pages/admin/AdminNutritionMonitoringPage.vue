<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '@/shared/components/layout/sidebarItems'
import FitnezCard from '@/shared/components/ui/FitnezCard.vue'
import ExcelImportModal from '@/shared/components/forms/TypedExcelImportModal.vue'
import { http } from '@/shared/api/http'
import { mealPlanApi } from '@/features/Mealplan/api/mealPlanApi'
import SkeletonStatGrid from '@/shared/components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '@/shared/components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'

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

const currentPage = ref(1)
const perPage = 15

const paginatedMembers = computed(() => {
  const start = (currentPage.value - 1) * perPage
  const end = start + perPage
  return members.value.slice(start, end)
})

const lastPage = computed(() => Math.ceil(members.value.length / perPage) || 1)

const visiblePages = computed(() => {
  const last = Number(lastPage.value)
  const current = Number(currentPage.value)
  if (last <= 5) {
    return Array.from({ length: last }, (_, i) => i + 1)
  }
  if (current <= 2) {
    return [1, 2, 3, '...', last]
  }
  if (current >= last - 1) {
    return [1, '...', last - 2, last - 1, last]
  }
  if (current === 3) {
    return [1, 2, 3, 4, '...', last]
  }
  if (current === last - 2) {
    return [1, '...', last - 3, last - 2, last - 1, last]
  }
  return [1, '...', current - 1, current, current + 1, '...', last]
})

function goToPage(p: number | string) {
  if (typeof p === 'string') return
  if (p < 1 || p > lastPage.value || p === currentPage.value) return
  currentPage.value = p
}

async function loadMembers() {
  error.value = ''
  try {
    const response = await mealPlanApi.getAllMemberNutrition()
    members.value = (response as any).members ?? []
    currentPage.value = 1
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
              <tr v-for="member in paginatedMembers" :key="member.id">
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

        <div v-if="members.length" class="pager-bar">
          <p>Page {{ currentPage }} of {{ lastPage }}</p>
          <div class="pagination">
            <button type="button" class="pagination-arrow" :disabled="currentPage <= 1" @click="goToPage(currentPage - 1)">‹</button>
            <button
              v-for="p in visiblePages"
              :key="p"
              :class="{ active: Number(p) === Number(currentPage), disabled: p === '...' }"
              :disabled="p === '...'"
              type="button"
              @click="goToPage(p)"
            >
              {{ p }}
            </button>
            <button type="button" class="pagination-arrow" :disabled="currentPage >= lastPage" @click="goToPage(currentPage + 1)">›</button>
          </div>
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

.pager-bar {
  align-items: center;
  border-top: 1px solid rgba(0, 0, 0, 0.10);
  display: flex;
  justify-content: space-between;
  padding: 0.9rem 1.25rem;
  margin-top: 1rem;
}

.pager-bar p {
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 700;
  margin: 0;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pagination button {
  background: white;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-family: inherit;
  font-size: 13px;
  font-weight: 700;
  min-width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  padding: 0;
}

.pagination button:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination button.active {
  background: #0058be;
  color: white;
  border-color: #0058be;
}

.pagination button:disabled {
  color: #cbd5e1;
  cursor: not-allowed;
  background: #f8fafc;
}
</style>
