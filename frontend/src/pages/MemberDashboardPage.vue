<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../stores/authStore'
import { useBrowserTrackingStore } from '../stores/browserTrackingStore'
import SkeletonCard from '../components/ui/SkeletonCard.vue'

const auth = useAuthStore()
const browser = useBrowserTrackingStore()
const loading = ref(true)

onMounted(() => {
  loading.value = false
})
</script>

<template>
  <main class="dashboard">
    <section class="card">
      <h1>Member Dashboard</h1>
      <p v-if="!loading">Welcome, {{auth.user?.full_name}}.</p>
      <SkeletonCard v-if="loading" heading :lines="2" wide />
      <template v-else>
        <div class="grid">
          <article><strong>Role</strong><span>{{auth.user?.role}}</span></article>
          <article><strong>Tab Mode</strong><span>{{browser.contextType}}</span></article>
          <article><strong>Current Tab Leader</strong><span>{{browser.isLeader?'Yes':'No'}}</span></article>
          <article><strong>Memory Rule</strong><span>Only leader tab should run heavy polling.</span></article>
        </div>
      </template>
    </section>
  </main>
</template>

<style scoped>
.dashboard{min-height:100vh;background:#f8fafc;padding:32px}.card{max-width:920px;margin:auto;background:white;border:1px solid #e2e8f0;border-radius:18px;padding:28px}.grid{margin-top:24px;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:16px}article{border:1px solid #e2e8f0;border-radius:14px;padding:16px;display:grid;gap:8px}</style>
