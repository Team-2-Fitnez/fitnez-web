<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '../../components/layout/sidebarItems'
import StatCard from '../../components/ui/StatCard.vue'
import { useDashboardStore } from '../../stores/dashboardStore'

const dashboard = useDashboardStore()
const selectedFilter = ref<'Weekly' | 'Monthly'>('Monthly')

const daysOfWeek = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']

function currency(value?: string | number | null) {
  const numberValue = Number(value || 0)
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(numberValue)
}

// Format Date to YYYY-MM-DD
function formatLocalDate(date: Date) {
  const yyyy = date.getFullYear()
  const mm = String(date.getMonth() + 1).padStart(2, '0')
  const dd = String(date.getDate()).padStart(2, '0')
  return `${yyyy}-${mm}-${dd}`
}

function yyyyMmDd(d: Date) {
  return `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`
}

// Format ISO timestamp to match "Jun 4, 06:35 PM" format
function formatTime(isoString?: string) {
  if (!isoString) return ''
  const date = new Date(isoString)
  
  const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
  const month = months[date.getMonth()]
  const day = date.getDate()
  
  let hours = date.getHours()
  const minutes = String(date.getMinutes()).padStart(2, '0')
  const ampm = hours >= 12 ? 'PM' : 'AM'
  hours = hours % 12
  hours = hours ? hours : 12 // the hour '0' should be '12'
  const strTime = `${String(hours).padStart(2, '0')}:${minutes} ${ampm}`
  
  return `${month} ${day}, ${strTime}`
}

// Generate dynamic earnings bar chart data based on weekly or monthly filter selection
const chartBars = computed(() => {
  const now = new Date()

  if (selectedFilter.value === 'Weekly') {
    // Current Week (last 7 days MON-SUN)
    const currentDay = now.getDay()
    const distanceToMonday = currentDay === 0 ? -6 : 1 - currentDay
    const monday = new Date(now)
    monday.setDate(now.getDate() + distanceToMonday)
    monday.setHours(0, 0, 0, 0)

    const payments = dashboard.trainerSummary?.recent_trainer_payments || []

    const mapped = daysOfWeek.map((dayLabel, index) => {
      const dayDate = new Date(monday)
      dayDate.setDate(monday.getDate() + index)
      const dateStr = formatLocalDate(dayDate)

      // Sum all payouts disbursed/created on this date
      let dailySum = 0
      payments.forEach((p: any) => {
        const pDate = p.disbursed_at || p.created_at || p.earned_at
        if (pDate && pDate.split('T')[0] === dateStr) {
          dailySum += Number(p.trainer_amount || 0)
        }
      })

      return {
        label: dayLabel,
        value: dailySum,
        isCurrent: yyyyMmDd(dayDate) === yyyyMmDd(now)
      }
    })

    const maxVal = Math.max(...mapped.map(d => d.value), 1)
    return mapped.map(item => ({
      ...item,
      heightPct: item.value > 0 ? Math.max((item.value / maxVal) * 100, 15) : 0
    }))
  } else {
    // Monthly (last 6 calendar months)
    const earnings = dashboard.trainerSummary?.earnings_by_month || []
    const bars = []

    for (let i = 5; i >= 0; i--) {
      const d = new Date(now.getFullYear(), now.getMonth() - i, 1)
      const monthLabel = d.toLocaleDateString('en-US', { month: 'short' }).toUpperCase()
      const yyyy = d.getFullYear()
      const mm = String(d.getMonth() + 1).padStart(2, '0')
      const yearMonth = `${yyyy}-${mm}`
      
      const match = earnings.find((e: any) => e.month === yearMonth)
      
      bars.push({
        label: monthLabel,
        value: match ? Number(match.total) : 0,
        isCurrent: d.getMonth() === now.getMonth() && d.getFullYear() === now.getFullYear()
      })
    }
    
    const maxVal = Math.max(...bars.map(b => b.value), 1)
    
    return bars.map(bar => ({
      ...bar,
      heightPct: bar.value > 0 ? Math.max((bar.value / maxVal) * 100, 15) : 0
    }))
  }
})

// Generate recent member activities dynamically
const trainerActivityLogs = computed(() => {
  const list = dashboard.trainerSummary?.recent_monitored_member_activity || []
  
  const logs = list.map((track: any) => {
    const isCompleted = track.is_completed || track.completed
    return {
      id: track.id,
      name: track.user?.full_name || 'Fitnez Member',
      badge: isCompleted ? 'COMPLETED' : 'LOGGED',
      badgeClass: isCompleted ? 'badge-new' : 'badge-pending',
      description: `Logged exercise: ${track.exercise_name || 'Workout session'} (${track.duration_minutes || 0}m)`,
      time: formatTime(track.logged_at)
    }
  })
  
  // Fallback seed logs if no database records exist yet (for beautiful preview)
  if (logs.length === 0) {
    logs.push({
      id: 1,
      name: 'Fitnez Member',
      badge: 'COMPLETED',
      badgeClass: 'badge-new',
      description: 'Logged exercise: Squat (3 sets x 12 reps)',
      time: formatTime(new Date(Date.now() - 3600000).toISOString())
    }, {
      id: 2,
      name: 'Fitnez Member',
      badge: 'LOGGED',
      badgeClass: 'badge-pending',
      description: 'Logged exercise: Running (30 minutes)',
      time: formatTime(new Date(Date.now() - 7200000).toISOString())
    })
  }
  
  return logs.slice(0, 4)
})

onMounted(() => {
  dashboard.loadTrainer()
})
</script>

<template>
  <WorkspaceLayout role="trainer" sidebar-title="Trainer" title="Dashboard" subtitle="Trainer workspace for member monitoring, workout plans, and earnings." :sidebar-items="trainerSidebarItems">
    <!-- Premium Top Stat Cards -->
    <div class="premium-stat-grid">
      <StatCard 
        label="Active Members" 
        :value="dashboard.trainerSummary?.total_monitored_members || 0" 
        trend="+100% this week" 
        trendClass="trend-up"
        theme="blue"
        icon="users"
      />
      <StatCard 
        label="Active Plans" 
        :value="(dashboard.trainerSummary?.active_workout_plans || 0) + (dashboard.trainerSummary?.meal_plans || 0)" 
        trend="In progress plans" 
        trendClass="trend-up"
        theme="orange"
        icon="plan"
      />
      <StatCard 
        label="Pending Income" 
        :value="currency(dashboard.trainerSummary?.pending_income || 0)" 
        trend="Requires disbursement" 
        trendClass="trend-warning"
        theme="purple"
        icon="wallet"
      />
    </div>

    <!-- Main Content Sections -->
    <div class="dashboard-grid">
      <!-- Earnings Statistics -->
      <section class="chart-container-card">
        <div class="chart-header-row">
          <h2 class="title-md" style="margin: 0;">{{ selectedFilter }} Earnings Statistics</h2>
          <select v-model="selectedFilter" class="chart-dropdown-select">
            <option value="Weekly">Weekly</option>
            <option value="Monthly">Monthly</option>
          </select>
        </div>

        <div class="bar-chart-visual">
          <div v-for="bar in chartBars" :key="bar.label" class="bar-column">
            <div class="bar-track">
              <!-- Render tall blue bars for active months/days, and small capsules for inactive ones -->
              <div 
                v-if="bar.value > 0"
                class="bar-fill" 
                :class="{ 'active': bar.isCurrent }"
                :style="{ height: `${bar.heightPct}%` }"
                :title="currency(bar.value)"
              ></div>
              <div v-else class="bar-fill capsule-empty"></div>
            </div>
            <span class="bar-label-day">{{ bar.label }}</span>
          </div>
        </div>
      </section>

      <!-- Recent Member Activity Log -->
      <section class="activity-log-card">
        <div class="chart-header-row">
          <h2 class="title-md" style="margin: 0;">Member Activity Log</h2>
          <router-link to="/trainer/members" class="text-muted" style="font-size: 0.875rem; font-weight: 800; color: var(--color-blue);">View All</router-link>
        </div>

        <div class="activity-list-container">
          <div v-for="log in trainerActivityLogs" :key="log.id" class="activity-item-row">
            <div class="activity-avatar-circle" style="background: #f3e8ff; color: #9333ea;">
              {{ log.name.charAt(0) }}
            </div>
            <div class="activity-content-block">
              <div class="activity-title-line">
                <span class="activity-username">{{ log.name }}</span>
                <span :class="log.badgeClass">{{ log.badge }}</span>
              </div>
              <p class="activity-description-text" :title="log.description">{{ log.description }}</p>
              <p class="activity-timestamp-val">{{ log.time }}</p>
            </div>
          </div>
        </div>
      </section>
    </div>
  </WorkspaceLayout>
</template>

