<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{ status: string }>()

const label = computed(() => String(props.status || 'unknown').replaceAll('_', ' '))

const statusClass = computed(() => {
  if (['approved', 'active', 'paid', 'success', 'completed'].includes(props.status)) return 'status status-success'
  if (['awaiting_admin_review', 'pending', 'awaiting_payment', 'scheduled'].includes(props.status)) return 'status status-warning'
  if (['rejected', 'inactive', 'failed', 'cancelled'].includes(props.status)) return 'status status-danger'
  return 'status status-info'
})
</script>

<template>
  <span :class="statusClass">{{ label }}</span>
</template>
