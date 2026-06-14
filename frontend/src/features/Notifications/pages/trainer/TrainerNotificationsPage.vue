<script setup lang="ts">
import { computed, onMounted, watch } from 'vue'
import WorkspaceLayout from '@/shared/components/layout/WorkspaceLayout.vue'
import { trainerSidebarItems } from '@/shared/components/layout/sidebarItems'
import { useNotificationStore } from '@/features/Notifications/stores/notificationStore'
import type { NotificationItem } from '@/features/Dashboard/types/dashboard'
import SkeletonList from '@/shared/components/ui/SkeletonList.vue'
import { useDeferredLoading } from '@/shared/composables/useDeferredLoading'
import { useAutoRefresh } from '@/shared/composables/useAutoRefresh'

const store = useNotificationStore()
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()

watch(() => store.unreadCount, (count) => {
  window.dispatchEvent(new CustomEvent('fitnez-update-unread', { detail: { hasUnread: count > 0 } }))
}, { immediate: true })

function formatTime(dateStr: string) {
  if (!dateStr) return '--:--'
  const date = new Date(dateStr)
  if (isNaN(date.getTime())) return '--:--'
  return date.toLocaleTimeString('en-US', {
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: false,
  })
}

function iconName(type: string) {
  if (type === 'payment_in' || type === 'rent') return 'payments'
  if (type === 'booking_request' || type === 'hire' || type === 'schedule') return 'calendar_today'
  if (type === 'classes') return 'fitness_center'
  return 'notifications'
}

function iconClass(type: string) {
  return {
    'notification-icon-trainer': type === 'payment_in' || type === 'rent',
    'notification-icon-member': type === 'booking_request' || type === 'hire' || type === 'schedule',
    'notification-icon-system': type === 'classes' || !type,
  }
}

function notificationPrefix(type: string) {
  if (type === 'payment_in' || type === 'rent') return 'Commission In'
  if (type === 'booking_request' || type === 'hire') return 'Trainer Hire'
  if (type === 'schedule') return 'Session Schedule'
  if (type === 'classes') return 'Workout Class'
  return 'Notification'
}

function nameClass(type: string) {
  if (type === 'payment_in' || type === 'rent') return 'notification-name-trainer'
  if (type === 'classes') return 'notification-name-system'
  return 'notification-name-member'
}

function statusLabel(item: NotificationItem) {
  return item.is_read ? 'Read' : 'New'
}

function statusClass(item: NotificationItem) {
  return item.is_read ? 'status-success' : 'status-warning'
}

async function markAsRead(id: number) {
  await store.markAsRead(id)
}

async function markAllRead() {
  await store.markAllRead()
}

const visiblePages = computed(() => {
  const last = Number(store.lastPage)
  const current = Number(store.page)
  if (last <= 5) return Array.from({ length: last }, (_, i) => i + 1)
  if (current <= 2) return [1, 2, 3, '...', last]
  if (current >= last - 1) return [1, '...', last - 2, last - 1, last]
  return [1, '...', current - 1, current, current + 1, '...', last]
})

function goToPage(page: number | string) {
  if (typeof page === 'string') return
  store.goToPage(page)
}

async function refreshData() {
  await Promise.all([store.load(), store.loadUnreadCount()])
}

onMounted(() => {
  run(refreshData)
})
useAutoRefresh(() => store.loadUnreadCount(), 8000)
</script>

<template>
  <WorkspaceLayout
    role="trainer"
    title="Notifications"
    subtitle="Monitor workout sessions, class schedules, and your incoming payments"
    sidebarTitle="Trainer"
    :sidebarItems="trainerSidebarItems"
  >
    <section class="notifications-page">
      <div class="notifications-shell">
        <SkeletonList v-if="initialLoading && !store.items.length" :rows="6" :style="shimmerStyle" />

        <section v-else-if="store.items.length" class="notification-group">
          <div class="notification-panel-head">
            <h3>Recent Activity Log</h3>
            <button
              v-if="store.unreadCount > 0"
              type="button"
              class="mark-all-button"
              :disabled="store.loading"
              @click="markAllRead"
            >
              Mark All as Read
            </button>
          </div>

          <div class="notification-list">
            <article
              v-for="item in store.items"
              :key="item.id"
              class="notification-item"
              :class="{ 'notification-item-unread': !item.is_read }"
              @click="!item.is_read && markAsRead(item.id)"
            >
              <div class="notification-icon" :class="iconClass(item.notification_type)">
                <span class="material-symbols-outlined" aria-hidden="true">{{ iconName(item.notification_type) }}</span>
              </div>

              <div class="notification-body">
                <div class="notification-mainline">
                  <h4>
                    <span>{{ notificationPrefix(item.notification_type) }}: </span>
                    <span :class="nameClass(item.notification_type)">{{ item.title }}</span>
                  </h4>
                  <time>{{ formatTime(item.created_at) }}</time>
                </div>
                <p>{{ item.body }}</p>
              </div>

              <div class="notification-meta">
                <time>{{ formatTime(item.created_at) }}</time>
                <span :class="['status-pill', statusClass(item)]">{{ statusLabel(item) }}</span>
              </div>
            </article>
          </div>

          <div v-if="store.lastPage > 1" class="pager-bar">
            <p>Page {{ store.page }} of {{ store.lastPage }}</p>
            <div class="pagination">
              <button type="button" :disabled="store.page <= 1 || store.loading" @click="goToPage(store.page - 1)">‹</button>
              <button
                v-for="page in visiblePages"
                :key="page"
                type="button"
                :class="{ active: Number(page) === Number(store.page), disabled: page === '...' }"
                :disabled="page === '...' || store.loading"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
              <button type="button" :disabled="store.page >= store.lastPage || store.loading" @click="goToPage(store.page + 1)">›</button>
            </div>
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
          <p>There are currently no new activities or alerts. Important notifications will appear here.</p>
        </section>
      </div>
    </section>
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

.mark-all-button:hover:not(:disabled) {
  background: #f1f3ff;
  border-color: #adc6ff;
  color: #0058be;
}

.mark-all-button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
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

.pager-bar {
  align-items: center;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  margin-top: 1.25rem;
  padding-top: 1rem;
}

.pager-bar p {
  color: #64748b;
  font-size: 0.82rem;
  font-weight: 800;
  margin: 0;
}

.pagination {
  align-items: center;
  display: flex;
  gap: 0.4rem;
}

.pagination button {
  align-items: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 0.5rem;
  color: #334155;
  cursor: pointer;
  display: inline-flex;
  font-family: inherit;
  font-size: 0.82rem;
  font-weight: 800;
  height: 2rem;
  justify-content: center;
  min-width: 2rem;
  padding: 0 0.55rem;
}

.pagination button.active {
  background: #0058be;
  border-color: #0058be;
  color: #ffffff;
}

.pagination button:disabled {
  background: #f8fafc;
  color: #cbd5e1;
  cursor: not-allowed;
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
  transition: background 0.15s, border-color 0.15s;
}

.notification-item:hover {
  background: #f8fafc;
  border-color: #d8dee9;
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

  .pager-bar {
    align-items: stretch;
    flex-direction: column;
    gap: 0.75rem;
  }

  .pagination {
    flex-wrap: wrap;
  }
}
</style>
