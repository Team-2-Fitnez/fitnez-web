<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { http as api } from '@/shared/api/http'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '@/shared/components/layout/sidebarItems'
import { RouterLink } from 'vue-router'
import SkeletonStatGrid from '@/shared/components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonChartBlock from '@/shared/components/ui/skeleton/SkeletonChartBlock.vue'
import SkeletonList from '@/shared/components/ui/SkeletonList.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { useAutoRefresh } from '@/shared/composables/useAutoRefresh'

type RecentActivity = {
  id: number
  full_name: string
  email: string
  created_at: string
}

type SummaryData = {
  users_total: number
  new_registrations_today: number
  trainers_total: number
  members_total: number
  schedules_today: number
  transactions_pending: number
  unread_notifications: number
  recent_activity: RecentActivity[]
  weekly_activations?: number[]
  monthly_activations?: number[]
  users_total_trend?: number
  trainers_total_trend?: number
}

const stats = ref<SummaryData>({
  users_total: 1248,
  new_registrations_today: 0,
  trainers_total: 45,
  members_total: 1203,
  schedules_today: 0,
  transactions_pending: 24,
  unread_notifications: 0,
  recent_activity: [],
  weekly_activations: [0, 0, 0, 0, 0, 0, 0],
  monthly_activations: [0, 0, 0, 0, 0, 0, 0],
  users_total_trend: 0,
  trainers_total_trend: 0
})

const selectedPeriod = ref('This Week')
const { loading, run, shimmerStyle } = useDeferredLoading()

async function loadSummary() {
  try {
    const resp = await api.get<SummaryData>('/dashboard/summary')
    if (resp.success && resp.data) {
      stats.value = resp.data
    }
  } catch (err) {
    console.error('Failed to load dashboard summary', err)
  }
}

onMounted(() => run(loadSummary))
useAutoRefresh(loadSummary, 8000)

const chartBars = computed(() => {
  const activeArray = selectedPeriod.value === 'This Week'
    ? (stats.value.weekly_activations || [0, 0, 0, 0, 0, 0, 0])
    : (stats.value.monthly_activations || [0, 0, 0, 0, 0, 0, 0])

  const maxVal = Math.max(...activeArray)

  return activeArray.map(val => {
    // If the database has no registrations, return a default visual baseline to keep the layout beautiful
    if (maxVal === 0) {
      return { height: '15%', value: 0 }
    }
    // Enforce a minimum aesthetic height of 8% so that bars do not become completely invisible
    const percent = Math.max(Math.round((val / maxVal) * 100), 8)
    return { height: `${percent}%`, value: val }
  })
})

function formatDate(dateStr: string) {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('en-US', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Dashboard"
    subtitle="System monitoring panel, registration verification, and transaction approval."
    :sidebar-items="adminSidebarItems"
  >
    <div class="max-w-7xl mx-auto w-full space-y-6" :style="shimmerStyle">
      <template v-if="loading">
        <SkeletonStatGrid :count="3" />
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2"><SkeletonChartBlock /></div>
          <SkeletonList :rows="5" :avatar="false" :lines="2" />
        </div>
      </template>
      <template v-else>
      <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between relative overflow-hidden p-6 md:p-8">
          <div class="flex justify-between items-start mb-6 relative z-10">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Total Users</h3>
            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </div>
          </div>
          <div class="relative z-10">
            <div class="text-4xl font-black text-gray-900 mb-2">{{ stats.users_total }}</div>
            <div
              :class="[
                'text-xs font-bold flex items-center gap-1.5',
                (stats.users_total_trend ?? 0) > 0 ? 'text-green-600' : (stats.users_total_trend ?? 0) < 0 ? 'text-red-600' : 'text-gray-500'
              ]"
            >
              <svg v-if="(stats.users_total_trend ?? 0) !== 0" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="(stats.users_total_trend ?? 0) > 0" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                <path v-else d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
              {{ (stats.users_total_trend ?? 0) > 0 ? '+' : '' }}{{ stats.users_total_trend ?? 0 }}% this week
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-24 opacity-20 pointer-events-none">
            <svg class="w-full h-full text-blue-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
              <path d="M0 40 L0 30 Q 25 10, 50 20 T 100 5 L100 40 Z"></path>
            </svg>
          </div>
        </div>

        <!-- Card 2 -->
        <div class="bg-white rounded-3xl shadow-sm border border-orange-100 flex flex-col justify-between relative overflow-hidden p-6 md:p-8">
          <div class="flex justify-between items-start mb-6 relative z-10">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Pending Verification</h3>
            <div class="p-2.5 bg-orange-50 text-orange-500 rounded-xl">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </div>
          </div>
          <div class="relative z-10">
            <div class="text-4xl font-black text-gray-900 mb-2">{{ stats.transactions_pending }}</div>
            <div class="text-xs text-orange-500 font-bold flex items-center gap-1.5">
              Requires approval action
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-24 opacity-20 pointer-events-none">
            <svg class="w-full h-full text-orange-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
              <path d="M0 40 L0 25 Q 25 35, 50 15 T 100 20 L100 40 Z"></path>
            </svg>
          </div>
        </div>

        <!-- Card 3 -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col justify-between relative overflow-hidden p-6 md:p-8">
          <div class="flex justify-between items-start mb-6 relative z-10">
            <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Active Trainers</h3>
            <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
            </div>
          </div>
          <div class="relative z-10">
            <div class="text-4xl font-black text-gray-900 mb-2">{{ stats.trainers_total }}</div>
            <div
              :class="[
                'text-xs font-bold flex items-center gap-1.5',
                (stats.trainers_total_trend ?? 0) > 0 ? 'text-green-600' : (stats.trainers_total_trend ?? 0) < 0 ? 'text-red-600' : 'text-gray-500'
              ]"
            >
              <svg v-if="(stats.trainers_total_trend ?? 0) !== 0" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path v-if="(stats.trainers_total_trend ?? 0) > 0" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                <path v-else d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
              </svg>
              {{ (stats.trainers_total_trend ?? 0) > 0 ? '+' : '' }}{{ stats.trainers_total_trend ?? 0 }}% this week
            </div>
          </div>
          <div class="absolute bottom-0 left-0 right-0 h-24 opacity-20 pointer-events-none">
            <svg class="w-full h-full text-purple-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
              <path d="M0 40 L0 15 Q 25 5, 50 25 T 100 10 L100 40 Z"></path>
            </svg>
          </div>
        </div>
      </section>

      <!-- Split Content Area -->
      <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-12">
        <!-- Chart Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col lg:col-span-2 p-6 md:p-8">
          <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Weekly Activity Statistics</h2>
            <select
              v-model="selectedPeriod"
              class="text-sm font-bold text-gray-600 bg-gray-50 border border-gray-200 rounded-xl focus:ring-0 cursor-pointer py-2 px-4 shadow-sm"
            >
              <option>This Week</option>
              <option>This Month</option>
            </select>
          </div>
          <div class="flex-1 flex items-end justify-between gap-4 h-56 mt-4">
            <div
              v-for="(bar, idx) in chartBars"
              :key="idx"
              :class="['w-full bg-blue-50 rounded-t-xl relative group hover:bg-blue-100 transition-all duration-500', idx === 4 ? 'bg-blue-600 shadow-md hover:bg-blue-700' : '']"
              :style="{ height: bar.height }"
            >
              <!-- Interactive Tooltip -->
              <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity z-20 pointer-events-none mb-1 whitespace-nowrap shadow-sm">
                {{ bar.value }} Registrations
              </span>
            </div>
          </div>
          <div class="flex justify-between text-xs text-gray-400 mt-4 font-bold tracking-wider uppercase px-2">
            <span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span>
          </div>
        </div>

        <!-- Recent Activities -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 lg:col-span-1 p-6 md:p-8 flex flex-col">
          <div class="flex justify-between items-center mb-8">
            <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Recent Activity Log</h2>
            <RouterLink to="/admin/check-in-logs" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
              View All
            </RouterLink>
          </div>
          <div class="space-y-5 flex-1 overflow-y-auto max-h-[350px] scrollbar-hide">
            <!-- Dynamic Activity Items -->
            <template v-if="stats.recent_activity && stats.recent_activity.length > 0">
              <div
                v-for="act in stats.recent_activity"
                :key="act.id"
                class="flex items-start gap-4 p-2 rounded-2xl hover:bg-gray-50 transition-colors group"
              >
                <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0 text-sm">
                  {{ act.full_name ? act.full_name[0].toUpperCase() : 'U' }}
                </div>
                <div class="flex-1">
                  <div class="flex items-center gap-2 mb-1">
                    <p class="text-sm font-bold text-gray-900">{{ act.full_name }}</p>
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-md text-[9px] font-black uppercase tracking-widest">New</span>
                  </div>
                  <p class="text-xs text-gray-500 font-medium truncate max-w-[180px]">Account registration with email {{ act.email }}</p>
                  <p class="text-[10px] mt-1.5 text-gray-400 font-bold">{{ formatDate(act.created_at) }}</p>
                </div>
                <RouterLink to="/admin/users" class="px-3 py-1.5 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
                  Review
                </RouterLink>
              </div>
            </template>

            <div v-if="!stats.recent_activity?.length" class="rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-5 text-center">
              <p class="text-sm font-extrabold text-gray-800">No recent activity yet</p>
              <p class="mt-1 text-xs font-semibold text-gray-500">New registrations and access logs will appear once users interact with the system.</p>
              <RouterLink to="/admin/check-in-logs" class="mt-4 inline-flex px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 shadow-sm">
                Open Check-In Logs
              </RouterLink>
            </div>
          </div>
        </div>
      </section>
      </template>
    </div>
  </WorkspaceLayout>
</template>
