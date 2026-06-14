<template>
  <div class="rounded-2xl border border-slate-200 bg-white p-4">
    <div class="mb-4 h-8 w-1/3 animate-pulse rounded bg-slate-200" />
    <div class="space-y-3">
      <div v-for="i in 6" :key="i" class="grid grid-cols-4 gap-3">
        <div class="h-6 animate-pulse rounded bg-slate-200" />
        <div class="h-6 animate-pulse rounded bg-slate-200" />
        <div class="h-6 animate-pulse rounded bg-slate-200" />
        <div class="h-6 animate-pulse rounded bg-slate-200" />
      </div>
    </div>
  </div>
</template>
