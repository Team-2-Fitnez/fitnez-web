<template>
  <WorkspaceLayout
    role="admin"
    title="Notifications"
    subtitle="Monitoring new registrations and active user statistics"
    sidebarTitle="Admin"
    :sidebarItems="adminSidebarItems"
  >
    <section class="notifications-page">
      <div class="notifications-shell">
        <section v-if="notifications.length > 0" class="notification-group">
          <div class="notification-panel-head">
            <h3>Recent Activity Log</h3>
            <button type="button" class="mark-all-button" @click="markAllRead">
              Mark All Read
            </button>
          </div>
          <div class="notification-list">
            <article
              v-for="item in notifications"
              :key="item.id"
              class="notification-item"
              :class="{ 'notification-item-unread': !isRead(item.id) }"
              @click="!isRead(item.id) && markAsRead(item.id)"
            >
              <div class="notification-icon" :class="iconClass(item.type)">
                <span class="material-symbols-outlined" aria-hidden="true">{{ iconName(item.type) }}</span>
              </div>

              <div class="notification-body">
                <div class="notification-mainline">
                  <h4>
                    <span>{{ notificationPrefix(item.type) }}: </span>
                    <span :class="nameClass(item.type)">{{ item.name || 'Anonymous' }}</span>
                  </h4>
                  <time>{{ formatTime(item.created_at) }}</time>
                </div>
                <p>{{ notificationDescription(item) }}</p>
              </div>

              <div class="notification-meta">
                <time>{{ formatTime(item.created_at) }}</time>
                <span :class="['status-pill', statusClass(item)]">{{ statusLabel(item) }}</span>
              </div>
            </article>
          </div>
        </section>

        <section v-else class="empty-notifications">
          <div class="notification-panel-head">
            <h3>Recent Activity Log</h3>
          </div>
          <div class="empty-icon">
            <span class="material-symbols-outlined" aria-hidden="true">notifications_paused</span>
          </div>
          <h3>No notifications yet</h3>
          <p>There is currently no new activity or alert. Important notifications will appear here.</p>
        </section>
      </div>
    </section>
  </WorkspaceLayout>
</template>

<script>
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import api from '@/api/axios'

export default {
  name: 'AdminNotificationView',
  components: { WorkspaceLayout },
  data() {
    return {
      adminSidebarItems,
      activeUsers: 0,
      pendingMemberCount: 0,
      pendingTrainerCount: 0,
      notifications: [],
      readAdminNotifIds: JSON.parse(localStorage.getItem('fitnez_admin_read_notifs') || '[]'),
    }
  },
  async mounted() {
    await this.fetchAdminData()
  },
  methods: {
    isRead(id) {
      return this.readAdminNotifIds.includes(id)
    },
    iconName(type) {
      if (type === 'member') return 'person_add'
      if (type === 'trainer') return 'badge'
      return 'check_circle'
    },
    iconClass(type) {
      return {
        'notification-icon-member': type === 'member',
        'notification-icon-trainer': type === 'trainer',
        'notification-icon-system': type === 'system',
      }
    },
    notificationPrefix(type) {
      if (type === 'member') return 'New Member Registration'
      if (type === 'trainer') return 'New Trainer Application'
      return 'System Update'
    },
    nameClass(type) {
      if (type === 'system') return 'notification-name-system'
      if (type === 'trainer') return 'notification-name-trainer'
      return 'notification-name-member'
    },
    notificationDescription(item) {
      const email = item.email || 'Email not available'
      const plan = item.plan || 'Package not configured'
      return `${email} - ${plan}`
    },
    statusLabel(item) {
      if (item.type === 'system') return 'Active'
      return item.status === 'awaiting_admin_review' || item.status === 'pending' ? 'Pending' : 'Completed'
    },
    statusClass(item) {
      if (item.type === 'system') return 'status-success'
      return item.status === 'awaiting_admin_review' || item.status === 'pending' ? 'status-warning' : 'status-success'
    },
    markAsRead(id) {
      if (!this.readAdminNotifIds.includes(id)) {
        this.readAdminNotifIds.push(id)
        localStorage.setItem('fitnez_admin_read_notifs', JSON.stringify(this.readAdminNotifIds))
      }
    },
    markAllRead() {
      this.notifications.forEach(n => {
        if (!this.readAdminNotifIds.includes(n.id)) {
          this.readAdminNotifIds.push(n.id)
        }
      })
      localStorage.setItem('fitnez_admin_read_notifs', JSON.stringify(this.readAdminNotifIds))
    },
    async fetchAdminData() {
      try {
        const { data } = await api.get('/admin/notifications')
        this.notifications = data.notifications || []
        this.activeUsers = data.activeUsers || 0
        this.pendingMemberCount = data.pendingMemberCount || 0
        this.pendingTrainerCount = data.pendingTrainerCount || 0
      } catch (error) {
        window.showFitnezToast('Failed to fetch admin data.', 'error')
      }
    },
    formatTime(dateStr) {
      if (!dateStr) return '--:--'
      const date = new Date(dateStr)
      if (isNaN(date.getTime())) return '--:--'
      return date.toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
      })
    },
    async approve(id) {
      try {
        await api.post(`/admin/approve/${id}`)
        this.notifications = this.notifications.filter(n => n.id !== id)
        this.activeUsers++
        window.showFitnezToast('Registration successfully approved', 'success')
      } catch (error) {
        window.showFitnezToast('Failed to approve registration', 'error')
      }
    },
    async reject(id) {
      try {
        await api.post(`/admin/reject/${id}`)
        this.notifications = this.notifications.filter(n => n.id !== id)
        window.showFitnezToast('Registration has been rejected', 'success')
      } catch (error) {
        window.showFitnezToast('Failed to reject registration', 'error')
      }
    },
  },
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
  padding-top: 3.25rem;
}

.mark-all-button {
  align-items: center;
  border-radius: 0.75rem;
  cursor: pointer;
  display: inline-flex;
  font-weight: 800;
  gap: 0.5rem;
  justify-content: center;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
  background: #ffffff;
  border: 1px solid #c2c6d6;
  color: #141b2b;
  font-size: 0.82rem;
  min-height: 2.5rem;
  padding: 0 1.05rem;
}

.mark-all-button:hover {
  background: #f1f3ff;
  border-color: #adc6ff;
  color: #0058be;
}

.notifications-shell {
  margin: 0 auto;
  max-width: 65rem;
  width: 100%;
}

.notification-group,
.empty-notifications {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 1.5rem;
  box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
  padding: 1.75rem;
}

.notification-panel-head {
  align-items: center;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
  margin-bottom: 1.25rem;
}

.notification-panel-head h3 {
  color: #141b2b;
  font-size: 0.98rem;
  font-weight: 600;
  line-height: 1.2;
  margin: 0;
}

.notification-list {
  display: grid;
  gap: 0.85rem;
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
  padding: 0.9rem 0.9rem;
  transition: background 0.15s, border-color 0.15s;
}

.notification-item:hover {
  background: #f8fafc;
  border-color: #d8dee9;
}

.notification-item-unread {
  cursor: pointer;
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
  margin-bottom: 0.2rem;
}

.notification-mainline h4 {
  color: #141b2b;
  font-size: 0.92rem;
  font-weight: 800;
  line-height: 1.35;
  margin: 0;
  min-width: 0;
}

.notification-name-member,
.notification-name-trainer {
  color: #0058be;
}

.notification-name-system {
  color: #16a34a;
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
  background: transparent;
  border-radius: 0;
  display: inline-flex;
  font-size: 0.65rem;
  font-weight: 900;
  letter-spacing: 0.04em;
  line-height: 1;
  padding: 0;
  text-transform: uppercase;
}

.status-success {
  color: #16a34a;
}

.status-warning {
  color: #b45309;
}

.empty-notifications {
  align-items: center;
  display: flex;
  flex-direction: column;
  min-height: 15rem;
  text-align: center;
}

.empty-notifications .notification-panel-head {
  align-self: stretch;
  margin-bottom: 1.25rem;
}

.empty-icon {
  align-items: center;
  background: #dce2f7;
  border-radius: 999px;
  color: #727785;
  display: inline-flex;
  height: 4rem;
  justify-content: center;
  margin-bottom: 1rem;
  width: 4rem;
}

.empty-icon .material-symbols-outlined {
  font-size: 2rem;
}

.empty-notifications h3 {
  color: #141b2b;
  font-size: 1.25rem;
  font-weight: 800;
  margin: 0 0 0.5rem;
}

.empty-notifications p {
  color: #424754;
  font-size: 0.9rem;
  line-height: 1.5;
  margin: 0;
  max-width: 24rem;
}

@media (max-width: 760px) {
  .notifications-page {
    padding-top: 1rem;
  }

  .notification-group,
  .empty-notifications {
    border-radius: 1rem;
    padding: 1rem;
  }

  .notification-panel-head {
    align-items: stretch;
    flex-direction: column;
  }

  .mark-all-button {
    width: 100%;
  }

  .notification-item {
    grid-template-columns: auto minmax(0, 1fr);
  }

  .notification-meta {
    align-items: flex-start;
    grid-column: 2;
    padding-top: 0;
  }

  .notification-mainline {
    flex-direction: column;
    gap: 0.25rem;
  }
}
</style>
