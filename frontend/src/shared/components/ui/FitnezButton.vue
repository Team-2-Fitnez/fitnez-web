<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  type?: 'button' | 'submit'
  variant?: 'primary' | 'secondary' | 'black' | 'ghost' | 'danger'
  size?: 'sm' | 'md' | 'lg'
  disabled?: boolean
}>(), {
  type: 'button',
  variant: 'primary',
  size: 'md',
  disabled: false,
})

const buttonClass = computed(() => [
  'button',
  `button-${props.variant}`,
  props.size === 'sm' && 'button-small',
  props.size === 'lg' && 'button-large',
])
</script>

<template>
  <button :type="type" :disabled="disabled" :class="buttonClass">
    <slot />
  </button>
</template>
