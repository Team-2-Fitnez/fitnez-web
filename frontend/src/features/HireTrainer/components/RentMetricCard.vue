<script setup lang="ts">
defineProps<{
  label: string
  value: string
  icon: string
  iconBgClass: string
  iconTextClass: string
}>()
</script>

<template>
  <div class="metric-box bg-white shadow-sm border border-gray-100">
    <div class="box-top">
      <span class="box-label">{{ label }}</span>
      <div :class="['icon-ring', iconBgClass, iconTextClass]">
        <span class="material-symbols-outlined">{{ icon }}</span>
      </div>
    </div>
    <div class="box-bottom">
      <div class="box-val">{{ value }}</div>
    </div>
  </div>
</template>

<style scoped>
.metric-box {
  border-radius: 1.5rem;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  height: 9.5rem;
}
.box-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}
.box-label {
  font-size: 0.75rem;
  font-weight: 800;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}
.icon-ring {
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.75rem;
  display: flex;
  align-items: center;
  justify-content: center;
}
.icon-ring .material-symbols-outlined {
  font-size: 1.15rem;
}
.box-bottom {
  margin-top: auto;
}
.box-val {
  font-size: 1.85rem;
  font-weight: 900;
  color: #111827;
  letter-spacing: -0.02em;
}
</style>
