<script setup lang="ts">
defineProps<{
  title: string
  columns: Array<{ key: string; label: string }>
  rows: Record<string, any>[]
  loading?: boolean
  page: number
  lastPage: number
  total: number
}>()

defineEmits<{
  search: [value: string]
  next: []
  previous: []
  create: []
}>()
</script>

<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">{{ title }}</h2>
        <p class="text-sm text-slate-500">{{ total }} total records</p>
      </div>

      <div class="flex gap-2">
        <input
          class="rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-500"
          placeholder="Search..."
          @input="$emit('search', ($event.target as HTMLInputElement).value)"
        />
        <button
          class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700"
          @click="$emit('create')"
        >
          Add
        </button>
      </div>
    </div>

    <div v-if="loading" class="p-4">
      <div class="space-y-3">
        <div v-for="i in 6" :key="i" class="h-8 animate-pulse rounded bg-slate-200" />
      </div>
    </div>

    <div v-else class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50">
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              class="px-4 py-3 text-left font-semibold text-slate-600"
            >
              {{ column.label }}
            </th>
            <th class="px-4 py-3 text-right font-semibold text-slate-600">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50">
            <td v-for="column in columns" :key="column.key" class="px-4 py-3 text-slate-700">
              <slot :name="`cell-${column.key}`" :row="row">
                {{ row[column.key] }}
              </slot>
            </td>
            <td class="px-4 py-3 text-right">
              <slot name="actions" :row="row" />
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="columns.length + 1" class="px-4 py-8 text-center text-slate-500">
              No records found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex items-center justify-between border-t border-slate-200 p-4">
      <p class="text-sm text-slate-500">Page {{ page }} of {{ lastPage }}</p>
      <div class="flex gap-2">
        <button
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:opacity-50"
          :disabled="page <= 1"
          @click="$emit('previous')"
        >
          Previous
        </button>
        <button
          class="rounded-lg border border-slate-300 px-3 py-2 text-sm disabled:opacity-50"
          :disabled="page >= lastPage"
          @click="$emit('next')"
        >
          Next
        </button>
      </div>
    </div>
  </section>
</template>
