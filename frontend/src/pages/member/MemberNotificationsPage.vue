<template>
  <WorkspaceLayout
    role="member"
    title="My Notifications"
    subtitle="Latest updates to your training schedule and activity notification log"
    sidebarTitle="Member"
    :sidebarItems="memberSidebarItems"
  >
    <section class="notifications-page">
      <div class="notifications-shell">
        <div class="notifications-grid">
          
          <!-- COLUMN 1: WORKOUT REMINDERS -->
          <section class="notification-group">
            <div class="notification-panel-head">
              <h3>Your Workout Schedule</h3>
            </div>

            <SkeletonList v-if="loading" :rows="6" />
            <div v-else-if="hariIni.length === 0 && besok.length === 0 && kemarin.length === 0" class="empty-state-card">
              <span class="material-symbols-outlined empty-icon-symbol">calendar_today</span>
              <h4>No training schedule</h4>
              <p>Workout plans created by your trainer will appear here.</p>
            </div>

            <div v-else class="workout-sections-list">
              <!-- TODAY -->
              <div v-if="hariIni.length > 0" class="workout-section-block">
                <h4 class="section-title text-accent">Today</h4>
                <div class="notification-list">
                  <article
                    v-for="item in hariIni"
                    :key="item.id"
                    class="notification-item"
                  >
                    <div class="notification-icon notification-icon-member">
                      <span class="material-symbols-outlined">fitness_center</span>
                    </div>
                    <div class="notification-body">
                      <div class="notification-mainline">
                        <h4>{{ item.title }}</h4>
                      </div>
                      <p>{{ item.body }}</p>
                    </div>
                    <div class="notification-meta">
                      <span v-if="item.is_read" class="status-pill status-success">Completed</span>
                      <span v-else class="status-pill status-warning">Pending</span>
                    </div>
                  </article>
                </div>
              </div>

              <!-- TOMORROW -->
              <div v-if="besok.length > 0" class="workout-section-block">
                <h4 class="section-title text-blue">Tomorrow / Upcoming</h4>
                <div class="notification-list">
                  <article
                    v-for="item in besok"
                    :key="item.id"
                    class="notification-item"
                  >
                    <div class="notification-icon notification-icon-member">
                      <span class="material-symbols-outlined">calendar_today</span>
                    </div>
                    <div class="notification-body">
                      <div class="notification-mainline">
                        <h4>{{ item.title }}</h4>
                      </div>
                      <p>{{ item.body }}</p>
                    </div>
                    <div class="notification-meta">
                      <span class="status-pill status-info">Upcoming</span>
                    </div>
                  </article>
                </div>
              </div>

              <!-- PREVIOUS / HISTORY -->
              <div v-if="kemarin.length > 0" class="workout-section-block">
                <h4 class="section-title text-gray">Completed (History)</h4>
                <div class="notification-list opacity-75">
                  <article
                    v-for="item in kemarin"
                    :key="item.id"
                    class="notification-item"
                  >
                    <div class="notification-icon notification-icon-system">
                      <span class="material-symbols-outlined">check_circle</span>
                    </div>
                    <div class="notification-body">
                      <div class="notification-mainline">
                        <h4>{{ item.title }}</h4>
                      </div>
                      <p>{{ item.body }}</p>
                    </div>
                    <div class="notification-meta">
                      <span class="status-pill status-success">Completed</span>
                    </div>
                  </article>
                </div>
              </div>
            </div>
          </section>

          <!-- COLUMN 2: SYSTEM NOTIFICATIONS -->
          <section class="notification-group">
            <div class="notification-panel-head">
              <h3>Notification Log</h3>
              <button
                v-if="hasUnreadNotifications"
                type="button"
                class="mark-all-button"
                @click="markAllReadMember"
              >
                Mark All Read
              </button>
            </div>

            <SkeletonList v-if="loading" :rows="6" />
            <div v-else-if="realNotifications.length === 0" class="empty-state-card">
              <span class="material-symbols-outlined empty-icon-symbol">notifications_paused</span>
              <h4>No notifications yet</h4>
              <p>System notifications or your new activity will be displayed in this column.</p>
            </div>

            <div v-else class="notification-list">
              <article
                v-for="item in realNotifications"
                :key="item.id"
                class="notification-item"
                :class="{ 'notification-item-unread': !item.is_read }"
                @click="!item.is_read && markAsRead(item.id)"
              >
                <div class="notification-icon" :class="getLogIconClass(item.notification_type)">
                  <span class="material-symbols-outlined">{{ getLogIconName(item.notification_type) }}</span>
                </div>

                <div class="notification-body">
                  <div class="notification-mainline">
                    <h4>
                      <span>{{ getLogPrefix(item.notification_type) }}: </span>
                      <span class="notification-title-highlight">{{ item.title }}</span>
                    </h4>
                    <time>{{ formatTime(item.created_at) }}</time>
                  </div>
                  <p>{{ item.body }}</p>
                </div>

                <div class="notification-meta">
                  <time>{{ formatTime(item.created_at) }}</time>
                  <span :class="['status-pill', item.is_read ? 'status-success' : 'status-warning']">
                    {{ item.is_read ? 'Read' : 'New' }}
                  </span>
                </div>
              </article>
            </div>
          </section>

        </div>
      </div>
    </section>
  </WorkspaceLayout>
</template>

<script>
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { memberSidebarItems } from '../../components/layout/sidebarItems'
import SkeletonList from '../../components/ui/SkeletonList.vue'
import api from '@/api/axios'

export default {
  name: 'MemberNotificationsPage',
  components: { WorkspaceLayout, SkeletonList },
  data() {
    return {
      loading: true,
      memberSidebarItems,
      realNotifications: [],
      kemarin: [],
      hariIni: [],
      besok: [],
      readDummyIds: JSON.parse(localStorage.getItem('fitnez_read_notifs') || '[]'),
    }
  },
  computed: {
    hasUnreadNotifications() {
      return this.realNotifications.some(n => !n.is_read)
    }
  },
  async mounted() {
    await this.fetchNotifications()
  },
  methods: {
    async fetchNotifications() {
      try {
        this.loading = true
        // 1. Fetch general notifications
        const respNotif = await api.get('/notifications')
        const rawRealNotifs = Array.isArray(respNotif.data) ? respNotif.data : (respNotif.data?.data || [])
        
        this.realNotifications = rawRealNotifs.map(n => ({
          ...n,
          is_read: n.is_read || this.readDummyIds.includes(n.id)
        }))

        // 2. Fetch workout plans
        const respWorkout = await api.get('/workout-plans')
        const workoutPlans = Array.isArray(respWorkout.data) ? respWorkout.data : (respWorkout.data?.data || [])
        
        const reminders = workoutPlans.map(w => ({
          id: 'workout-' + w.id,
          title: w.name,
          body: `${w.category} - ${w.reps} reps x ${w.set} set - ${w.weight}kg`,
          created_at: w.created_at,
          group_date: w.date,
          is_read: !!w.completed,
          type: 'workout'
        }))

        this.groupWorkoutReminders(reminders)
      } catch (error) {
        window.showFitnezToast('Failed to load notifications.', 'error')
      } finally {
        this.loading = false
      }
    },

    groupWorkoutReminders(reminders) {
      this.kemarin = []
      this.hariIni = []
      this.besok = []

      const today = new Date()
      today.setHours(0, 0, 0, 0)

      const yesterday = new Date(today)
      yesterday.setDate(yesterday.getDate() - 1)

      const tomorrow = new Date(today)
      tomorrow.setDate(tomorrow.getDate() + 1)

      reminders.forEach(n => {
        const d = new Date(n.group_date)
        d.setHours(0, 0, 0, 0)

        if (d.getTime() === today.getTime()) {
          this.hariIni.push(n)
        } else if (d.getTime() <= yesterday.getTime()) {
          this.kemarin.push(n)
        } else if (d.getTime() >= tomorrow.getTime()) {
          this.besok.push(n)
        }
      })
    },

    getLogIconName(type) {
      if (type === 'payment' || type === 'invoice') return 'payments'
      if (type === 'class' || type === 'booking') return 'event_available'
      return 'info'
    },

    getLogIconClass(type) {
      return {
        'notification-icon-trainer': type === 'payment' || type === 'invoice',
        'notification-icon-member': type === 'class' || type === 'booking',
        'notification-icon-system': !type || (type !== 'payment' && type !== 'invoice' && type !== 'class' && type !== 'booking')
      }
    },

    getLogPrefix(type) {
      if (type === 'payment' || type === 'invoice') return 'Payment'
      if (type === 'class' || type === 'booking') return 'Class Session'
      return 'Information'
    },

    formatTime(dateStr) {
      if (!dateStr) return '--:--'
      const date = new Date(dateStr)
      if (isNaN(date.getTime())) return '--:--'
      
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      })
    },

    async markAsRead(id) {
      if (typeof id === 'string' && id.startsWith('workout-')) {
        if (!this.readDummyIds.includes(id)) {
          this.readDummyIds.push(id)
          localStorage.setItem('fitnez_read_notifs', JSON.stringify(this.readDummyIds))
        }
        return
      }

      try {
        await api.patch(`/notifications/${id}/read`)
        await this.fetchNotifications()
      } catch (error) {
        window.showFitnezToast('Failed to update read status.', 'error')
      }
    },

    async markAllReadMember() {
      try {
        await api.patch('/notifications/read-all')
        await this.fetchNotifications()
        window.showFitnezToast('All notifications marked as read.', 'success')
      } catch (e) {
        window.showFitnezToast('Failed to update read status.', 'error')
      }
    }
  }
}
</script>

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

.notifications-page {
  color: #141b2b;
  display: flex;
  flex-direction: column;
  padding-top: 1.5rem;
}

.notifications-shell {
  margin: 0 auto;
  max-width: 76rem;
  width: 100%;
}

.notifications-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 2rem;
}

@media (min-width: 1024px) {
  .notifications-grid {
    grid-template-columns: 1fr 1fr;
  }
}

.notification-group {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 1.5rem;
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.notification-panel-head {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  border-bottom: 1px solid #f1f5f9;
  padding-bottom: 1rem;
}

.notification-panel-head h3 {
  color: #141b2b;
  font-size: 1.1rem;
  font-weight: 800;
  line-height: 1.2;
  margin: 0;
  letter-spacing: -0.02em;
}

.mark-all-button {
  align-items: center;
  border-radius: 0.75rem;
  cursor: pointer;
  display: inline-flex;
  font-weight: 800;
  gap: 0.5rem;
  justify-content: center;
  transition: all 0.2s ease;
  background: #ffffff;
  border: 1px solid #c2c6d6;
  color: #141b2b;
  font-size: 0.8rem;
  min-height: 2.25rem;
  padding: 0 0.85rem;
}

.mark-all-button:hover {
  background: #f1f3ff;
  border-color: #adc6ff;
  color: #0058be;
}

.workout-sections-list {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.workout-section-block {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.section-title {
  font-size: 0.8rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0;
  padding-left: 0.25rem;
}

.text-accent {
  color: #ea580c;
}

.text-blue {
  color: #2563eb;
}

.text-gray {
  color: #64748b;
}

.notification-list {
  display: grid;
  gap: 0.75rem;
}

.notification-item {
  align-items: flex-start;
  background: #ffffff;
  border: 1px solid #e7eaf0;
  border-radius: 0.75rem;
  cursor: default;
  display: grid;
  gap: 0.9rem;
  grid-template-columns: auto minmax(0, 1fr) auto;
  min-height: 4.25rem;
  padding: 0.9rem;
  transition: all 0.15s ease;
}

.notification-item:hover {
  background: #f8fafc;
  border-color: #d8dee9;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}

.notification-item-unread {
  cursor: pointer;
  border-left-width: 4px;
  border-left-color: #0058be;
}

.notification-icon {
  align-items: center;
  border-radius: 999px;
  display: inline-flex;
  height: 2rem;
  justify-content: center;
  margin-top: 0.15rem;
  width: 2rem;
}

.notification-icon .material-symbols-outlined {
  font-size: 1.15rem;
}

.notification-icon-member {
  background: #d8e2ff;
  color: #004395;
}

.notification-icon-trainer {
  background: #ffddb8;
  color: #653e00;
}

.notification-icon-system {
  background: #d1fae5;
  color: #065f46;
}

.notification-mainline {
  align-items: flex-start;
  display: flex;
  gap: 0.75rem;
  justify-content: space-between;
  margin-bottom: 0.25rem;
}

.notification-mainline h4 {
  color: #141b2b;
  font-size: 0.88rem;
  font-weight: 800;
  line-height: 1.35;
  margin: 0;
  min-width: 0;
}

.notification-title-highlight {
  color: #0058be;
}

.notification-mainline time {
  display: none;
}

.notification-body p {
  color: #727785;
  font-size: 0.78rem;
  font-weight: 700;
  line-height: 1.35;
  margin: 0;
}

.notification-meta {
  align-items: flex-end;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  padding-top: 0.1rem;
}

.notification-meta time {
  color: #0f172a;
  font-size: 0.72rem;
  font-weight: 800;
  white-space: nowrap;
}

.status-pill {
  border-radius: 0.5rem;
  display: inline-flex;
  font-size: 0.65rem;
  font-weight: 900;
  letter-spacing: 0.04em;
  line-height: 1;
  padding: 0.35rem 0.55rem;
  text-transform: uppercase;
}

.status-success {
  background: #f0fdf4;
  color: #16a34a;
}

.status-warning {
  background: #fffbeb;
  color: #b45309;
}

.status-info {
  background: #eff6ff;
  color: #1e40af;
}

.empty-state-card {
  align-items: center;
  display: flex;
  flex-direction: column;
  min-height: 15rem;
  text-align: center;
  justify-content: center;
  background: #f8fafc;
  border: 1px dashed #e2e8f0;
  border-radius: 1rem;
  padding: 2rem;
}

.empty-icon-symbol {
  font-size: 2.5rem;
  color: #94a3b8;
  margin-bottom: 0.75rem;
}

.empty-state-card h4 {
  color: #1e293b;
  font-size: 1rem;
  font-weight: 800;
  margin: 0 0 0.25rem 0;
}

.empty-state-card p {
  color: #64748b;
  font-size: 0.8rem;
  margin: 0;
  max-width: 18rem;
  line-height: 1.4;
}

.opacity-75 {
  opacity: 0.75;
}
</style>
