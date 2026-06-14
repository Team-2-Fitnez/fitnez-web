<script setup lang="ts">
defineProps<{
  label: string
  modelValue: string | number | null | undefined
  type?: string
  placeholder?: string
  error?: string
  disabled?: boolean
}>()

defineEmits<{ 'update:modelValue': [value: string] }>()
</script>

<template>
  <label class="form-field">
    <span class="form-label">{{ label }}</span>
    <input
      :type="type || 'text'"
      :value="modelValue ?? ''"
      :placeholder="placeholder"
      :disabled="disabled"
      class="form-input"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <span v-if="error" class="form-error">{{ error }}</span>
  </label>
</template>
