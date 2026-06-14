<script setup lang="ts">
defineProps<{
  label: string
  modelValue: string | number | null | undefined
  type?: string
  placeholder?: string
  error?: string
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()
</script>

<template>
  <label class="grid gap-1.5">
    <span class="text-sm font-semibold text-slate-700">{{ label }}</span>
    <input
      :type="type || 'text'"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      class="rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-sky-500 focus:ring-2 focus:ring-sky-100"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <span v-if="error" class="text-xs text-red-600">{{ error }}</span>
  </label>
</template>
