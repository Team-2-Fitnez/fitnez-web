<script setup lang="ts">defineProps<{variant?:'primary'|'secondary'}>()</script>
<template><button :class="['app-button', variant || 'primary']"><slot /></button></template>
<style scoped>.app-button{border:0;border-radius:10px;padding:12px;font-weight:700;cursor:pointer}.primary{background:#0f172a;color:white}.secondary{background:#e2e8f0;color:#0f172a}</style>
