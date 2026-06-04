<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import { useBookingStore } from '../../stores/bookingStore'
import { useTrainerMemberMonitoringStore } from '../../stores/trainerMemberMonitoringStore'
import { useTrainerRentHistoryStore } from '../../stores/trainerRentHistoryStore'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

const monitoringStore = useTrainerMemberMonitoringStore()
const rentStore = useTrainerRentHistoryStore()
const bookingStore = useBookingStore()
const { loading, run, shimmerStyle } = useDeferredLoading()

function money(value?: number | string | null) {
  return `Rp ${Number(value || 0).toLocaleString('en-US')}`
}

async function loadData() {
  await Promise.all([
    monitoringStore.loadSummary(),
    monitoringStore.loadMembers(),
    bookingStore.loadBookings(),
    rentStore.loadSummary(),
    rentStore.load(),
  ])
}

onMounted(() => run(loadData))

const confirmedBookings = computed(() => bookingStore.bookings.filter(item => item.status === 'confirmed').length)
const pendingBookings = computed(() => bookingStore.bookings.filter(item => item.status === 'pending').length)
const completedBookings = computed(() => bookingStore.bookings.filter(item => item.status === 'completed').length)

const completionRate = computed(() => {
  const plans = Number(monitoringStore.summary?.active_workout_plans || 0)
  const logs = Number(monitoringStore.summary?.completed_trackings || 0)
  if (!plans && !logs) return 0
  return Math.min(100, Math.round((logs / Math.max(plans * 4, 1)) * 100))
})

const incomeRate = computed(() => {
  const total = Number(rentStore.summary?.total_trainer_amount || 0)
  const disbursed = Number(rentStore.summary?.disbursed_amount || 0)
  if (!total) return 0
  return Math.min(100, Math.round((disbursed / total) * 100))
})

const programCards = computed(() => [
  {
    label: 'Workout Plans',
    value: monitoringStore.summary?.active_workout_plans || 0,
    hint: 'active programs',
    to: '/trainer/members',
  },
  {
    label: 'Nutrition',
    value: monitoringStore.summary?.meal_plans || 0,
    hint: 'meal plans tracked',
    to: '/trainer/members',
  },
  {
    label: 'Training Logs',
    value: monitoringStore.summary?.completed_trackings || 0,
    hint: 'completed logs',
    to: '/trainer/members',
  },
  {
    label: 'Schedule',
    value: confirmedBookings.value + completedBookings.value,
    hint: 'active & completed sessions',
    to: '/trainer/schedule',
  },
])

const chartBars = computed(() => {
  const values = programCards.value.map(item => Number(item.value || 0))
  const maxVal = Math.max(...values, 0)
  return programCards.value.map((item) => {
    if (maxVal === 0) {
      return { ...item, height: '15%' }
    }
    const percent = Math.max(Math.round((Number(item.value) / maxVal) * 100), 8)
    return {
      ...item,
      height: `${percent}%`,
    }
  })
})

const workspaceLinks = [
  { label: 'Schedule', hint: 'Manage session requests and schedules.', to: '/trainer/schedule', status: 'Connected' },
  { label: 'Members', hint: 'Monitor member workouts, nutrition, and progress.', to: '/trainer/members', status: 'Real Data' },
  { label: 'Classes', hint: 'Class modules available to manage.', to: '/trainer/classes', status: 'Module Ready' },
  { label: 'Trainer Reports', hint: 'View earnings and commission history.', to: '/trainer/rent-history', status: 'Active' },
  { label: 'Chat', hint: 'Reply to member messages from connected schedule.', to: '/trainer/chat', status: 'Open' },
  { label: 'Notifications', hint: 'Monitor important trainer workspace updates.', to: '/trainer/notifications', status: 'Monitor' },
  { label: 'Profile', hint: 'Update trainer identity and status.', to: '/trainer/profile', status: 'Active' },
]
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Trainer Dashboard"
    subtitle="Trainer operations, member workouts, schedule requests, commissions, and notifications."
    :sidebar-items="trainerSidebarItems"
  >
    <div class="max-w-7xl mx-auto w-full space-y-6" :style="shimmerStyle">
      <template v-if="loading">
        <SkeletonStatGrid :count="3" />
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2"><SkeletonCard heading :lines="4" wide /></div>
          <div class="lg:col-span-1"><SkeletonCard :lines="5" /></div>
        </div>
      </template>

      <template v-else>
        <!-- Dashboard Toolbar / Header -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center p-6 md:p-8 gap-4">
          <div>
            <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Trainer Workspace</p>
            <h2 class="text-xl md:text-2xl font-black text-gray-900 mt-1">Trainer operations in a single summary.</h2>
          </div>
          <button
            class="px-4 py-2.5 bg-gray-900 hover:bg-gray-800 text-white rounded-xl text-sm font-bold transition-colors cursor-pointer"
            type="button"
            :disabled="loading"
            @click="loadData"
          >
            {{ loading ? 'Loading...' : 'Refresh' }}
          </button>
        </div>

        <!-- Metric Cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Card 1: Monitored Members -->
          <RouterLink
            to="/trainer/members"
            class="bg-white rounded-3xl shadow-sm border border-gray-100 hover:border-blue-200 transition-all duration-300 flex flex-col justify-between relative overflow-hidden p-6 md:p-8 group"
          >
            <div class="flex justify-between items-start mb-6 relative z-10">
              <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Monitored Members</h3>
              <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m8-4a4 4 0 11-8 0 4 4 0 018 0zm-8 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                </svg>
              </div>
            </div>
            <div class="relative z-10">
              <div class="text-4xl font-black text-gray-900 mb-2">{{ monitoringStore.summary?.total_members || 0 }}</div>
              <div
                :class="[
                  'text-xs font-bold flex items-center gap-1.5',
                  (monitoringStore.summary?.total_members_trend ?? 0) > 0 ? 'text-green-600' : (monitoringStore.summary?.total_members_trend ?? 0) < 0 ? 'text-red-600' : 'text-gray-500'
                ]"
              >
                <svg v-if="(monitoringStore.summary?.total_members_trend ?? 0) !== 0" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="(monitoringStore.summary?.total_members_trend ?? 0) > 0" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                  <path v-else d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                {{ (monitoringStore.summary?.total_members_trend ?? 0) > 0 ? '+' : '' }}{{ monitoringStore.summary?.total_members_trend ?? 0 }}% this week
              </div>
            </div>
            <p class="text-xs text-gray-400 font-bold mt-2 relative z-10">{{ monitoringStore.summary?.active_workout_plans || 0 }} active workout plans</p>
            <div class="absolute bottom-0 left-0 right-0 h-24 opacity-10 pointer-events-none group-hover:opacity-15 transition-opacity">
              <svg class="w-full h-full text-blue-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
                <path d="M0 40 L0 30 Q 25 10, 50 20 T 100 5 L100 40 Z"></path>
              </svg>
            </div>
          </RouterLink>

          <!-- Card 2: Schedule Requests -->
          <RouterLink
            to="/trainer/schedule"
            class="bg-white rounded-3xl shadow-sm border border-gray-100 hover:border-orange-200 transition-all duration-300 flex flex-col justify-between relative overflow-hidden p-6 md:p-8 group"
          >
            <div class="flex justify-between items-start mb-6 relative z-10">
              <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Schedule Requests</h3>
              <div class="p-2.5 bg-orange-50 text-orange-500 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M12 8v5l3 2m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                </svg>
              </div>
            </div>
            <div class="relative z-10">
              <div class="text-4xl font-black text-gray-900 mb-2">{{ pendingBookings }}</div>
              <div class="text-xs text-orange-500 font-bold flex items-center gap-1.5">
                Pending confirmation actions
              </div>
            </div>
            <p class="text-xs text-gray-400 font-bold mt-2 relative z-10">{{ confirmedBookings }} sessions confirmed</p>
            <div class="absolute bottom-0 left-0 right-0 h-24 opacity-10 pointer-events-none group-hover:opacity-15 transition-opacity">
              <svg class="w-full h-full text-orange-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
                <path d="M0 40 L0 25 Q 25 35, 50 15 T 100 20 L100 40 Z"></path>
              </svg>
            </div>
          </RouterLink>

          <!-- Card 3: Trainer Earnings -->
          <RouterLink
            to="/trainer/rent-history"
            class="bg-white rounded-3xl shadow-sm border border-gray-100 hover:border-emerald-200 transition-all duration-300 flex flex-col justify-between relative overflow-hidden p-6 md:p-8 group"
          >
            <div class="flex justify-between items-start mb-6 relative z-10">
              <h3 class="text-xs font-bold uppercase tracking-widest text-gray-500">Trainer Earnings</h3>
              <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3m-2 4h.01M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                </svg>
              </div>
            </div>
            <div class="relative z-10">
              <div class="text-4xl font-black text-gray-900 mb-2">{{ money(rentStore.summary?.total_trainer_amount) }}</div>
              <div
                :class="[
                  'text-xs font-bold flex items-center gap-1.5',
                  (rentStore.summary?.total_trainer_amount_trend ?? 0) > 0 ? 'text-green-600' : (rentStore.summary?.total_trainer_amount_trend ?? 0) < 0 ? 'text-red-600' : 'text-gray-500'
                ]"
              >
                <svg v-if="(rentStore.summary?.total_trainer_amount_trend ?? 0) !== 0" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path v-if="(rentStore.summary?.total_trainer_amount_trend ?? 0) > 0" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                  <path v-else d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                </svg>
                {{ (rentStore.summary?.total_trainer_amount_trend ?? 0) > 0 ? '+' : '' }}{{ rentStore.summary?.total_trainer_amount_trend ?? 0 }}% this week
              </div>
            </div>
            <p class="text-xs text-gray-400 font-bold mt-2 relative z-10">{{ rentStore.summary?.total_records || 0 }} transaction reports</p>
            <div class="absolute bottom-0 left-0 right-0 h-24 opacity-10 pointer-events-none group-hover:opacity-15 transition-opacity">
              <svg class="w-full h-full text-emerald-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
                <path d="M0 40 L0 15 Q 25 5, 50 25 T 100 10 L100 40 Z"></path>
              </svg>
            </div>
          </RouterLink>
        </section>

        <!-- Split Content Area -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left: Program Stats Chart (col-span-2) -->
          <div class="bg-white rounded-3xl shadow-sm border border-gray-100 flex flex-col lg:col-span-2 p-6 md:p-8">
            <div class="flex justify-between items-center mb-8">
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600 font-black">Program Stats</p>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight mt-1">Member Activity & Sessions</h2>
              </div>
              <RouterLink to="/trainer/members" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
                Detail
              </RouterLink>
            </div>

            <div class="flex-1 flex items-end justify-between gap-4 h-56 mt-4">
              <div
                v-for="(bar, idx) in chartBars"
                :key="idx"
                :class="['w-full bg-blue-50 rounded-t-xl relative group hover:bg-blue-100 transition-all duration-500', idx === 0 ? 'bg-blue-600 shadow-md hover:bg-blue-700' : '']"
                :style="{ height: bar.height }"
              >
                <!-- Interactive Tooltip -->
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 bg-gray-800 text-white text-[10px] py-1 px-2 rounded opacity-0 group-hover:opacity-100 transition-opacity z-20 pointer-events-none mb-1 whitespace-nowrap shadow-sm">
                  {{ bar.value }} {{ bar.label }}
                </span>
              </div>
            </div>

            <div class="grid grid-cols-4 text-center text-xs text-gray-400 mt-6 font-bold tracking-wider uppercase px-2">
              <RouterLink v-for="item in programCards" :key="item.label" :to="item.to" class="hover:text-blue-600 transition-colors flex flex-col items-center">
                <span class="text-gray-900 font-extrabold text-[11px]">{{ item.label }}</span>
                <span class="text-[9px] text-gray-400 font-medium normal-case mt-0.5">{{ item.hint }}</span>
              </RouterLink>
            </div>
          </div>

          <!-- Right: Recent Clients List (col-span-1) -->
          <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 flex flex-col">
            <div class="flex justify-between items-center mb-8">
              <div>
                <p class="text-xs font-bold uppercase tracking-widest text-blue-600 font-black">Recent Clients</p>
                <h2 class="text-xl font-extrabold text-gray-900 tracking-tight mt-1">Under Monitoring</h2>
              </div>
              <RouterLink to="/trainer/members" class="text-sm font-bold text-blue-600 hover:text-blue-800 transition-colors">
                All
              </RouterLink>
            </div>

            <div class="space-y-4 flex-1 overflow-y-auto max-h-[350px] scrollbar-hide">
              <template v-if="monitoringStore.members && monitoringStore.members.length > 0">
                <div
                  v-for="member in monitoringStore.members.slice(0, 5)"
                  :key="member.id"
                  class="flex items-center gap-4 p-2 rounded-2xl hover:bg-gray-50 transition-colors group"
                >
                  <div class="w-10 h-10 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center font-bold flex-shrink-0 text-sm">
                    {{ member.full_name ? member.full_name[0].toUpperCase() : 'M' }}
                  </div>
                  <div class="flex-1 min-width-0">
                    <p class="text-sm font-bold text-gray-900 truncate">{{ member.full_name }}</p>
                    <p class="text-xs text-gray-500 font-medium truncate mt-0.5">
                      {{ member.workout_plans_count || 0 }} plans • {{ member.workout_trackings_count || 0 }} logs
                    </p>
                  </div>
                  <RouterLink
                    to="/trainer/members"
                    class="px-2.5 py-1.5 bg-white border border-gray-200 rounded-xl text-[10px] font-bold text-gray-700 hover:bg-gray-50 shadow-sm opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0"
                  >
                    Review
                  </RouterLink>
                </div>
              </template>
              <div v-else class="text-center py-8">
                <p class="text-xs text-gray-400 font-bold">No active clients connected yet.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- Bottom Grid Section: Quick Links & Summary Cards Stacked -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6 pb-12">
          <!-- Left: Workspace Links (col-span-2) -->
          <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 md:p-8 lg:col-span-2">
            <h2 class="text-xl font-extrabold text-gray-900 tracking-tight mb-6">Quick Links & Modules</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
              <RouterLink
                v-for="item in workspaceLinks"
                :key="item.label"
                :to="item.to"
                class="p-4 bg-gray-50 hover:bg-gray-100 border border-gray-100 rounded-2xl transition-all duration-300 group flex flex-col justify-between min-h-[95px]"
              >
                <div>
                  <div class="flex justify-between items-center">
                    <strong class="text-sm font-extrabold text-gray-900 group-hover:text-blue-600 transition-colors">{{ item.label }}</strong>
                    <span class="text-[8px] font-black uppercase tracking-widest px-2 py-0.5 bg-blue-50 text-blue-700 rounded-md">
                      {{ item.status }}
                    </span>
                  </div>
                  <span class="block text-[11px] text-gray-500 font-medium mt-2 leading-snug">
                    {{ item.hint }}
                  </span>
                </div>
              </RouterLink>
            </div>
          </div>

          <!-- Right: Consistency & Commissions stack (col-span-1) -->
          <div class="lg:col-span-1 space-y-6">
            <!-- Consistency (Completion Rate) -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 flex flex-col items-center justify-center">
              <div class="w-full flex justify-between items-start mb-2">
                <div>
                  <p class="text-xs font-black uppercase tracking-widest text-blue-600">Consistency</p>
                  <h3 class="text-sm font-black text-gray-900 mt-1">Program Completion</h3>
                </div>
              </div>
              <div class="w-28 h-28 rounded-full flex flex-col items-center justify-center relative my-4" :style="`background: conic-gradient(#2563eb ${completionRate}%, #e2e8f0 0)`">
                <div class="absolute inset-2 bg-white rounded-full flex flex-col items-center justify-center">
                  <strong class="text-xl font-black text-gray-900">{{ completionRate }}%</strong>
                  <span class="text-[9px] text-gray-400 font-bold uppercase mt-0.5">completion</span>
                </div>
              </div>
              <p class="text-[11px] text-gray-500 font-medium text-center px-4 leading-normal">
                {{ monitoringStore.summary?.completed_trackings || 0 }} logs completed out of {{ monitoringStore.summary?.active_workout_plans || 0 }} active programs.
              </p>
            </div>

            <!-- Commission Progress -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-6 flex flex-col">
              <div class="flex justify-between items-start mb-2">
                <div>
                  <p class="text-xs font-black uppercase tracking-widest text-emerald-600">Earnings</p>
                  <h3 class="text-sm font-black text-gray-900 mt-1">Commissions Paid</h3>
                </div>
                <RouterLink to="/trainer/rent-history" class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors">
                  History
                </RouterLink>
              </div>
              <p class="text-2xl font-black text-gray-900 mt-3">{{ money(rentStore.summary?.total_trainer_amount) }}</p>
              <div class="w-full bg-gray-100 rounded-full h-2 my-3 overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${incomeRate}%` }"></div>
              </div>
              <p class="text-[11px] text-gray-500 font-medium leading-normal">
                Disbursed {{ money(rentStore.summary?.disbursed_amount) }} of total commission ({{ incomeRate }}%).
              </p>
            </div>
          </div>
        </section>
      </template>
    </div>
  </WorkspaceLayout>
</template>
