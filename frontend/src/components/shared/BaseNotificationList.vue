<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  notifications: Array<any>
  role: 'admin' | 'trainer' | 'member'
}>()

// Filter or map notifications based on role
const filteredNotifications = computed(() => {
  if (props.role === 'trainer') {
    // Only show notifications relevant to trainer
    return props.notifications.filter(n => [
      'trainer-booked',
      'trainer-payment',
      'trainer-class-reminder',
      'trainer-schedule-reminder',
    ].includes(n.type))
  }
  if (props.role === 'admin') {
    return props.notifications.filter(n => [
      'admin-payment',
      'admin-trainer-approval',
      'admin-account-activated',
    ].includes(n.type))
  }
  // Default: member, show all except admin/trainer specific
  return props.notifications.filter(n => ![
    'trainer-booked',
    'trainer-payment',
    'trainer-class-reminder',
    'trainer-schedule-reminder',
    'admin-payment',
    'admin-trainer-approval',
    'admin-account-activated',
  ].includes(n.type))
})
</script>

<template>
  <div class="base-notification-list">
    <div v-if="filteredNotifications.length === 0" class="empty">Belum ada notifikasi.</div>
    <ul v-else>
      <li v-for="notif in filteredNotifications" :key="notif.id" class="notification-item">
        <slot :notification="notif">
          <div>
            <strong>{{ notif.title }}</strong>
            <div class="desc">{{ notif.message }}</div>
            <div class="meta">{{ notif.created_at }}</div>
          </div>
        </slot>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.base-notification-list {
  padding: 1rem;
}
.notification-item {
  border-bottom: 1px solid #eee;
  padding: 0.75rem 0;
}
.empty {
  color: #888;
  text-align: center;
  padding: 2rem 0;
}
.desc {
  font-size: 0.95em;
  color: #555;
}
.meta {
  font-size: 0.8em;
  color: #aaa;
}
</style>
