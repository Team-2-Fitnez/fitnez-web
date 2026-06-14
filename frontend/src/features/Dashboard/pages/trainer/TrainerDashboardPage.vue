<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '@/shared/components/layout/sidebarItems'
import { useBookingStore } from '@/features/HireTrainer/stores/bookingStore'
import { useTrainerMemberMonitoringStore } from '@/features/MemberProgressMonitoring/stores/trainerMemberMonitoringStore'
import { useTrainerRentHistoryStore } from '@/features/RentHistory/stores/trainerRentHistoryStore'
import SkeletonStatGrid from '@/shared/components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonCard from '@/shared/components/ui/SkeletonCard.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { useAutoRefresh } from '@/shared/composables/useAutoRefresh'

const monitoringStore = useTrainerMemberMonitoringStore()
const rentStore = useTrainerRentHistoryStore()
const bookingStore = useBookingStore()
const { loading, run, shimmerStyle } = useDeferredLoading()

const viewMode = ref<'weekly' | 'monthly'>('weekly')
const showDropdown = ref(false)

function setViewMode(mode: 'weekly' | 'monthly') {
  viewMode.value = mode
  showDropdown.value = false
}

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
useAutoRefresh(loadData, 8000)

function getStartOfWeek(d: Date) {
  const day = d.getDay()
  const diff = d.getDate() - day + (day === 0 ? -6 : 1)
  const start = new Date(d.setDate(diff))
  start.setHours(0, 0, 0, 0)
  return start
}

const weeklyEarnings = computed(() => {
  const days = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']
  const earnings = [0, 0, 0, 0, 0, 0, 0]

  const today = new Date()
  const startOfWeek = getStartOfWeek(new Date(today))

  const dayDates = Array.from({ length: 7 }, (_, i) => {
    const d = new Date(startOfWeek)
    d.setDate(startOfWeek.getDate() + i)
    return d.toDateString()
  })

  if (rentStore.items && rentStore.items.length > 0) {
    rentStore.items.forEach(item => {
      const dateStr = item.payment?.payment_date || item.booking?.start_date
      if (dateStr) {
        const itemDate = new Date(dateStr)
        const dayIndex = dayDates.indexOf(itemDate.toDateString())
        if (dayIndex !== -1) {
          earnings[dayIndex] += Number(item.trainer_amount || 0)
        }
      }
    })
  }

  const maxVal = Math.max(...earnings, 0)

  return days.map((day, idx) => {
    const val = earnings[idx]
    const percent = maxVal > 0 ? Math.max(Math.round((val / maxVal) * 100), 8) : 0
    return {
      day,
      value: val,
      height: `${percent}%`
    }
  })
})

const monthlyEarnings = computed(() => {
  const earnings = [0, 0, 0, 0, 0, 0]
  const today = new Date()
  
  const monthLabels: string[] = []
  const monthKeys: string[] = []

  for (let i = 5; i >= 0; i--) {
    const d = new Date(today.getFullYear(), today.getMonth() - i, 1)
    const label = d.toLocaleString('en-US', { month: 'short' }).toUpperCase()
    monthLabels.push(label)
    
    const year = d.getFullYear()
    const month = String(d.getMonth() + 1).padStart(2, '0')
    monthKeys.push(`${year}-${month}`)
  }

  if (rentStore.items && rentStore.items.length > 0) {
    rentStore.items.forEach(item => {
      const dateStr = item.payment?.payment_date || item.booking?.start_date
      if (dateStr) {
        const itemDate = new Date(dateStr)
        const year = itemDate.getFullYear()
        const month = String(itemDate.getMonth() + 1).padStart(2, '0')
        const key = `${year}-${month}`
        
        const idx = monthKeys.indexOf(key)
        if (idx !== -1) {
          earnings[idx] += Number(item.trainer_amount || 0)
        }
      }
    })
  }

  const maxVal = Math.max(...earnings, 0)

  return monthLabels.map((month, idx) => {
    const val = earnings[idx]
    const percent = maxVal > 0 ? Math.max(Math.round((val / maxVal) * 100), 8) : 0
    return {
      day: month,
      value: val,
      height: `${percent}%`
    }
  })
})

const chartData = computed(() => {
  return viewMode.value === 'weekly' ? weeklyEarnings.value : monthlyEarnings.value
})

function formatLogTime(dateInput: string | Date) {
  if (!dateInput) return ''
  const date = typeof dateInput === 'string' ? new Date(dateInput) : dateInput
  return date.toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true
  })
}

const recentLogs = computed(() => {
  const apiTrackings = (monitoringStore.summary as any)?.recent_trackings || []
  if (apiTrackings.length > 0) {
    return apiTrackings.map((t: any) => {
      const sets = t.actual_sets || t.workout_exercise?.sets || 3
      const reps = t.actual_reps || t.workout_exercise?.reps || 12
      const exName = t.workout_exercise?.exercise?.name || 'Squat'
      const isCardio = exName.toLowerCase().includes('run') || exName.toLowerCase().includes('cardio')
      const detail = isCardio 
        ? `Logged exercise: ${exName} (30 minutes)`
        : `Logged exercise: ${exName} (${sets} sets x ${reps} reps)`
      
      return {
        id: t.id,
        userName: t.user?.full_name || 'Fitnez Member',
        badgeType: t.is_completed ? 'COMPLETED' : 'LOGGED',
        detail,
        time: formatLogTime(t.logged_at),
        avatarInitial: (t.user?.full_name || 'F')[0].toUpperCase()
      }
    })
  }

  return [
    {
      id: 'mock-1',
      userName: 'Fitnez Member',
      badgeType: 'COMPLETED',
      detail: 'Logged exercise: Squat (3 sets x 12 reps)',
      time: 'Jun 7, 07:00 PM',
      avatarInitial: 'F'
    },
    {
      id: 'mock-2',
      userName: 'Fitnez Member',
      badgeType: 'LOGGED',
      detail: 'Logged exercise: Running (30 minutes)',
      time: 'Jun 7, 06:00 PM',
      avatarInitial: 'F'
    }
  ]
})
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    sidebar-title="Trainer"
    title="Dashboard"
    subtitle="Trainer workspace for member monitoring, workout plans, and earnings."
    :sidebar-items="trainerSidebarItems"
  >

    <div class="max-w-7xl mx-auto w-full space-y-6 pb-12" :style="shimmerStyle">
      <template v-if="loading">
        <SkeletonStatGrid :count="3" />
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2"><SkeletonCard heading :lines="4" wide /></div>
          <div class="lg:col-span-1"><SkeletonCard :lines="5" /></div>
        </div>
      </template>

      <template v-else>
        <!-- Metric Cards -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Card 1: ACTIVE MEMBERS -->
          <RouterLink
            to="/trainer/members"
            class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100/80 hover:border-blue-200 transition-all duration-300 flex flex-col justify-between relative overflow-hidden p-6 md:p-8 group"
          >
            <div class="flex justify-between items-start mb-6 relative z-10">
              <h3 class="text-[11px] font-black uppercase tracking-widest text-gray-500">Active Members</h3>
              <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M17 20h5v-2a4 4 0 00-4-4h-1M9 20H2v-2a4 4 0 014-4h3m8-4a4 4 0 11-8 0 4 4 0 018 0zm-8 0a4 4 0 11-8 0 4 4 0 018 0z" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </div>
            </div>
            <div class="relative z-10">
              <div class="text-4xl font-black text-gray-900 mb-2">{{ monitoringStore.summary?.total_members || 0 }}</div>
              <div class="text-[11px] font-bold flex items-center gap-1.5 text-green-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M7 17L17 7M17 7H9M17 7V15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                </svg>
                +100% this week
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-24 opacity-10 pointer-events-none group-hover:opacity-15 transition-opacity">
              <svg class="w-full h-full text-blue-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
                <path d="M0 40 L0 30 Q 25 10, 50 20 T 100 5 L100 40 Z"></path>
              </svg>
            </div>
          </RouterLink>

          <!-- Card 2: ACTIVE PLANS -->
          <RouterLink
            to="/trainer/members"
            class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100/80 hover:border-orange-200 transition-all duration-300 flex flex-col justify-between relative overflow-hidden p-6 md:p-8 group"
          >
            <div class="flex justify-between items-start mb-6 relative z-10">
              <h3 class="text-[11px] font-black uppercase tracking-widest text-gray-500">Active Plans</h3>
              <div class="p-2.5 bg-orange-50 text-orange-500 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
              </div>
            </div>
            <div class="relative z-10">
              <div class="text-4xl font-black text-gray-900 mb-2">{{ monitoringStore.summary?.active_workout_plans || 0 }}</div>
              <div class="text-[11px] font-bold flex items-center gap-1.5 text-green-600">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path d="M7 17L17 7M17 7H9M17 7V15" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"></path>
                </svg>
                In progress plans
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-24 opacity-10 pointer-events-none group-hover:opacity-15 transition-opacity">
              <svg class="w-full h-full text-orange-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
                <path d="M0 40 L0 25 Q 25 35, 50 15 T 100 20 L100 40 Z"></path>
              </svg>
            </div>
          </RouterLink>

          <!-- Card 3: PENDING INCOME -->
          <RouterLink
            to="/trainer/rent-history"
            class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100/80 hover:border-purple-200 transition-all duration-300 flex flex-col justify-between relative overflow-hidden p-6 md:p-8 group"
          >
            <div class="flex justify-between items-start mb-6 relative z-10">
              <h3 class="text-[11px] font-black uppercase tracking-widest text-gray-500">Pending Income</h3>
              <div class="p-2.5 bg-purple-50 text-purple-600 rounded-xl">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path d="M3 10h18M5 10V7a2 2 0 012-2h10a2 2 0 012 2v3m-2 4h.01M5 10v9a2 2 0 002 2h10a2 2 0 002-2v-9" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </div>
            </div>
            <div class="relative z-10">
              <div class="text-4xl font-black text-gray-900 mb-2">{{ money(rentStore.summary?.pending_amount ?? 0) }}</div>
              <div class="text-[11px] font-bold text-orange-600">
                Requires disbursement
              </div>
            </div>
            <div class="absolute bottom-0 left-0 right-0 h-24 opacity-10 pointer-events-none group-hover:opacity-15 transition-opacity">
              <svg class="w-full h-full text-purple-500 fill-current" preserveAspectRatio="none" viewBox="0 0 100 40">
                <path d="M0 40 L0 15 Q 25 5, 50 25 T 100 10 L100 40 Z"></path>
              </svg>
            </div>
          </RouterLink>
        </section>

        <!-- Main Dashboard Content -->
        <section class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <!-- Left: Earnings Statistics (col-span-2) -->
          <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100/80 p-6 md:p-8 lg:col-span-2 flex flex-col justify-between min-h-[380px]">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">
                {{ viewMode === 'weekly' ? 'Weekly' : 'Monthly' }} Earnings Statistics
              </h2>
              <!-- Custom dropdown menu -->
              <div class="relative">
                <button 
                  @click="showDropdown = !showDropdown"
                  class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 rounded-xl text-xs font-bold text-gray-700 hover:bg-gray-50 shadow-sm transition-all"
                  type="button"
                >
                  {{ viewMode === 'weekly' ? 'Weekly' : 'Monthly' }}
                  <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path d="M19 9l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" />
                  </svg>
                </button>
                <div v-if="showDropdown" class="absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-30">
                  <button 
                    @click="setViewMode('weekly')"
                    class="w-full text-left px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    Weekly
                  </button>
                  <button 
                    @click="setViewMode('monthly')"
                    class="w-full text-left px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 transition-colors"
                  >
                    Monthly
                  </button>
                </div>
              </div>
            </div>

            <!-- Chart Container -->
            <div class="flex-1 flex items-end justify-around gap-2 md:gap-4 px-2 md:px-6 h-56 min-h-[200px] mb-2">
              <div 
                v-for="day in chartData" 
                :key="day.day" 
                class="flex-1 flex flex-col items-center justify-end h-full group relative"
              >
                <!-- Tooltip -->
                <span class="absolute bottom-full left-1/2 transform -translate-x-1/2 bg-gray-900 text-white text-[10px] font-bold py-1.5 px-3 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity z-20 pointer-events-none mb-2 whitespace-nowrap shadow-md">
                  {{ money(day.value) }}
                </span>
                
                <!-- Zero/Empty Bar State -->
                <div 
                  v-if="day.value === 0" 
                  class="w-10 h-3 bg-gray-100 rounded-full mb-4 shadow-sm"
                ></div>
                <!-- Populated Bar State -->
                <div 
                  v-else 
                  class="w-10 rounded-t-full mb-4 bg-gradient-to-t from-blue-600 to-blue-400 transition-all duration-500 hover:scale-105 hover:shadow-lg"
                  :style="{ height: day.height }"
                ></div>
                
                <!-- Day label -->
                <span class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-wider">{{ day.day }}</span>
              </div>
            </div>
          </div>

          <!-- Right: Member Activity Log (col-span-1) -->
          <div class="bg-white rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.02)] border border-gray-100/80 p-6 md:p-8 flex flex-col justify-between min-h-[380px]">
            <div class="flex justify-between items-center mb-6">
              <h2 class="text-xl font-extrabold text-gray-900 tracking-tight">Member Activity Log</h2>
              <RouterLink 
                to="/trainer/members" 
                class="text-xs font-bold text-blue-600 hover:text-blue-800 transition-colors"
              >
                View All
              </RouterLink>
            </div>

            <!-- Activity Logs List -->
            <div class="space-y-6 flex-1 overflow-y-auto max-h-[320px] scrollbar-hide pr-1">
              <div 
                v-for="log in recentLogs" 
                :key="log.id" 
                class="flex items-start gap-4 p-1 rounded-2xl hover:bg-gray-50/50 transition-colors"
              >
                <!-- Avatar initial -->
                <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-black flex-shrink-0 text-sm shadow-sm">
                  {{ log.avatarInitial }}
                </div>
                
                <!-- Details -->
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-extrabold text-gray-900 truncate">{{ log.userName }}</span>
                    <!-- Badge -->
                    <span 
                      :class="[
                        'text-[8px] font-black uppercase tracking-wider px-2 py-0.5 rounded-md flex-shrink-0',
                        log.badgeType === 'COMPLETED' ? 'bg-[#f1f5f9] text-[#475569]' : 'bg-[#ffedd5] text-[#ea580c]'
                      ]"
                    >
                      {{ log.badgeType }}
                    </span>
                  </div>
                  <p class="text-xs text-gray-500 font-medium mt-1 leading-normal">{{ log.detail }}</p>
                  <p class="text-[10px] text-gray-400 font-medium mt-1">{{ log.time }}</p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </template>
    </div>
  </WorkspaceLayout>
</template>
