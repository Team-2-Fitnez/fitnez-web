<script setup lang="ts">
defineProps<{
  variant?: 'primary' | 'secondary' | 'danger'
  type?: 'button' | 'submit'
  disabled?: boolean
}>()
</script>

<template>
  <button
    :type="type || 'button'"
    :disabled="disabled"
    :class="[
      'rounded-xl px-4 py-2.5 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50',
      variant === 'secondary' && 'bg-slate-100 text-slate-900 hover:bg-slate-200',
      variant === 'danger' && 'bg-red-600 text-white hover:bg-red-700',
      (!variant || variant === 'primary') && 'bg-slate-900 text-white hover:bg-slate-700',
    ]"
  >
    <slot />
  </button>
</template>
