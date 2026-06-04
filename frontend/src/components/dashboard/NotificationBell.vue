<script setup lang="ts">
import { onMounted } from 'vue'
import { useNotificationStore } from '../../stores/notificationStore'

const store = useNotificationStore()

onMounted(() => {
  store.loadUnreadCount()
})
</script>

<template>
  <button class="relative rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700">
    Notifications
    <span
      v-if="store.unreadCount > 0"
      class="absolute -right-2 -top-2 rounded-full bg-red-600 px-2 py-0.5 text-xs text-white"
    >
      {{ store.unreadCount }}
    </span>
  </button>
</template>
