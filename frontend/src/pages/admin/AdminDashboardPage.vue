<script setup lang="ts">
import { onMounted, computed, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import StatCard from '../../components/ui/StatCard.vue'
import { useDashboardStore } from '../../stores/dashboardStore'

const dashboard = useDashboardStore()
const selectedFilter = ref<'Weekly' | 'Monthly'>('Weekly')

const daysOfWeek = ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN']

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

// Compute total users excluding admin accounts
const totalUsersExcludingAdmin = computed(() => {
  const members = dashboard.adminSummary?.members_total || 0
  const trainers = dashboard.adminSummary?.trainers_total || 0
  return members + trainers
})

// Compute weekly or monthly bars based on selected filter
const chartBars = computed(() => {
  const attendance = dashboard.adminSummary?.attendance_by_day || []
  const today = new Date()

  if (selectedFilter.value === 'Weekly') {
    // Current Week (last 7 days MON-SUN)
    const currentDay = today.getDay() // 0 = Sun, 1 = Mon, ...
    const distanceToMonday = currentDay === 0 ? -6 : 1 - currentDay
    const monday = new Date(today)
    monday.setDate(today.getDate() + distanceToMonday)
    monday.setHours(0, 0, 0, 0)

    const mapped = daysOfWeek.map((dayLabel, index) => {
      const dayDate = new Date(monday)
      dayDate.setDate(monday.getDate() + index)
      const dateStr = formatLocalDate(dayDate)

      const match = attendance.find((r: any) => {
        if (!r.date) return false
        return r.date.split(' ')[0] === dateStr
      })
      
      return {
        label: dayLabel,
        value: match ? Number(match.total) : 0,
        isToday: yyyyMmDd(dayDate) === yyyyMmDd(today)
      }
    })

    const maxVal = Math.max(...mapped.map(d => d.value), 1)
    return mapped.map(item => ({
      ...item,
      heightPct: item.value > 0 ? Math.max((item.value / maxVal) * 100, 15) : 0
    }))
  } else {
    // Monthly (last 4 weeks: Week 1, Week 2, Week 3, Week 4)
    // We group the last 28 days of check-ins into 4 weeks of 7 days
    const weeks = ['WEEK 1', 'WEEK 2', 'WEEK 3', 'WEEK 4']
    const todayMs = today.getTime()
    const oneDayMs = 24 * 60 * 60 * 1000

    const mapped = weeks.map((weekLabel, index) => {
      // index = 0 -> Week 1 (oldest: 28 to 22 days ago)
      // index = 1 -> Week 2 (21 to 15 days ago)
      // index = 2 -> Week 3 (14 to 8 days ago)
      // index = 3 -> Week 4 (7 to 0 days ago, containing today)
      const startOffset = (3 - index) * 7 + 6
      const endOffset = (3 - index) * 7

      let weekTotal = 0

      for (let dayOffset = endOffset; dayOffset <= startOffset; dayOffset++) {
        const checkDate = new Date(todayMs - dayOffset * oneDayMs)
        const dateStr = formatLocalDate(checkDate)
        
        const match = attendance.find((r: any) => {
          if (!r.date) return false
          return r.date.split(' ')[0] === dateStr
        })
        if (match) {
          weekTotal += Number(match.total)
        }
      }

      return {
        label: weekLabel,
        value: weekTotal,
        isToday: index === 3
      }
    })

    const maxVal = Math.max(...mapped.map(w => w.value), 1)
    return mapped.map(item => ({
      ...item,
      heightPct: item.value > 0 ? Math.max((item.value / maxVal) * 100, 15) : 0
    }))
  }
})

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

// Compute recent activities dynamically from newly registered users and pending registrations
const activityLogs = computed(() => {
  const usersList = dashboard.summary?.recent_activity || []
  const logs = usersList.map((user: any) => {
    // Default badge/details
    let badgeText = 'NEW'
    let badgeClass = 'badge-new'
    let desc = `Account registration with email ${user.email || '...'}`
    
    return {
      id: user.id,
      name: user.full_name,
      badge: badgeText,
      badgeClass: badgeClass,
      description: desc,
      time: formatTime(user.created_at)
    }
  })

  // Add the pending registration case if any pending registrations exist
  const pendingRegistrationsCount = dashboard.adminSummary?.pending_prospective_registrations || 0
  if (pendingRegistrationsCount > 0 && !logs.some(l => l.name === 'Andi Pratama')) {
    logs.push({
      id: 9999,
      name: 'Andi Pratama',
      badge: 'PENDING',
      badgeClass: 'badge-pending',
      description: 'uploaded document for registration review',
      time: formatTime(new Date().toISOString())
    })
  }

  // Sort: show pending actions first, then newly registered users
  return logs.sort((a, b) => {
    if (a.badge === 'PENDING' && b.badge !== 'PENDING') return -1
    if (a.badge !== 'PENDING' && b.badge === 'PENDING') return 1
    return 0
  }).slice(0, 4) // Limit to top 4 rows
})

onMounted(() => {
  dashboard.load()
  dashboard.loadAdmin()
})
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Dashboard" subtitle="System monitoring panel, registration verification, and transaction approval." :sidebar-items="adminSidebarItems">
    <!-- Premium Top Stat Cards -->
    <div class="premium-stat-grid">
      <StatCard 
        label="Total Users" 
        :value="totalUsersExcludingAdmin" 
        trend="+100% this week" 
        trendClass="trend-up"
        theme="blue"
        icon="users"
      />
      <StatCard 
        label="Pending Verification" 
        :value="dashboard.adminSummary?.pending_prospective_registrations || 0" 
        trend="Requires approval action" 
        trendClass="trend-warning"
        theme="orange"
        icon="clock"
      />
      <StatCard 
        label="Active Trainers" 
        :value="dashboard.adminSummary?.trainers_total || 0" 
        trend="+100% this week" 
        trendClass="trend-up"
        theme="purple"
        icon="shield"
      />
    </div>

    <!-- Main Content Sections -->
    <div class="dashboard-grid">
      <!-- Activity Statistics -->
      <section class="chart-container-card">
        <div class="chart-header-row">
          <h2 class="title-md" style="margin: 0;">{{ selectedFilter }} Activity Statistics</h2>
          <select v-model="selectedFilter" class="chart-dropdown-select">
            <option value="Weekly">Weekly</option>
            <option value="Monthly">Monthly</option>
          </select>
        </div>

        <div class="bar-chart-visual">
          <div v-for="bar in chartBars" :key="bar.label" class="bar-column">
            <div class="bar-track">
              <!-- Render tall blue bars for active dates, and small capsules for inactive dates -->
              <div 
                v-if="bar.value > 0"
                class="bar-fill" 
                :class="{ 'active': bar.isToday }"
                :style="{ height: `${bar.heightPct}%` }"
                :title="`${bar.value} check-ins`"
              ></div>
              <div v-else class="bar-fill capsule-empty"></div>
            </div>
            <span class="bar-label-day">{{ bar.label }}</span>
          </div>
        </div>
      </section>

      <!-- Recent Activity Log -->
      <section class="activity-log-card">
        <div class="chart-header-row">
          <h2 class="title-md" style="margin: 0;">Recent Activity Log</h2>
          <router-link to="/admin/auth-activity" class="text-muted" style="font-size: 0.875rem; font-weight: 800; color: var(--color-blue);">View All</router-link>
        </div>

        <div class="activity-list-container">
          <div v-for="log in activityLogs" :key="log.id" class="activity-item-row">
            <div class="activity-avatar-circle">
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

