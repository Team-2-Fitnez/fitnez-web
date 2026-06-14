<script setup lang="ts">
const emit = defineEmits<{
  openCookieSettings: []
}>()
</script>

<template>
  <footer class="site-footer">
    <p>© 2026 FitNez. All rights reserved.</p>

    <button type="button" class="cookie-footer-link" @click="emit('openCookieSettings')">
      Cookie Settings
    </button>
  </footer>
</template>

<style scoped>
.site-footer {
  display: flex;
  flex-wrap: wrap;
  gap: 12px 18px;
  align-items: center;
  justify-content: space-between;
  padding: 24px;
  color: #141414;
  background: #fff4df;
}

.site-footer p {
  margin: 0;
}

.cookie-footer-link {
  border: 0;
  padding: 0;
  background: transparent;
  color: #141414;
  font: inherit;
  font-weight: 800;
  text-decoration: underline;
  text-decoration-color: #f6a21a;
  text-decoration-thickness: 3px;
  text-underline-offset: 5px;
  cursor: pointer;
}
</style>
