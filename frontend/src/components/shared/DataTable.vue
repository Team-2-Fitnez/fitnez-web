<script setup lang="ts">
import { computed } from 'vue'

type DataTableColumn = {
  key: string
  label: string
  mobileLabel?: string
  hideOnMobile?: boolean
}

const props = withDefaults(defineProps<{
  title: string
  columns: DataTableColumn[]
  rows: Record<string, any>[]
  loading?: boolean
  page: number
  lastPage: number
  total: number
  mobileTitleKey?: string
  mobileSubtitleKey?: string
  mobileEmptyLabel?: string
}>(), {
  mobileEmptyLabel: 'No records found.',
})

defineEmits<{
  search: [value: string]
  next: []
  previous: []
  create: []
}>()

function valueFor(row: Record<string, any>, key?: string) {
  if (!key) return ''
  const value = row[key]
  if (value === null || value === undefined || value === '') return '-'
  return value
}

function mobileTitle(row: Record<string, any>) {
  return valueFor(row, props.mobileTitleKey || props.columns[0]?.key)
}

function mobileSubtitle(row: Record<string, any>) {
  return valueFor(row, props.mobileSubtitleKey || props.columns[1]?.key)
}

const mobileDetailColumns = computed(() =>
  props.columns.filter(
    (column) =>
      !column.hideOnMobile &&
      column.key !== props.mobileTitleKey &&
      column.key !== props.mobileSubtitleKey,
  ),
)
</script>

<template>
  <section class="rounded-2xl border border-slate-200 bg-white shadow-sm">
    <div class="flex flex-col gap-3 border-b border-slate-200 p-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h2 class="text-lg font-bold text-slate-900">{{ title }}</h2>
        <p class="text-sm text-slate-500">{{ total }} total records</p>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <input
          class="min-w-0 rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:border-sky-500"
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

    <template v-else>
      <div class="hidden overflow-x-auto md:block">
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

      <div class="space-y-3 p-3 md:hidden">
        <article
          v-for="(row, rowIndex) in rows"
          :key="row.id ?? rowIndex"
          class="mobile-record-card"
        >
          <slot name="mobile-card" :row="row">
            <div class="mobile-record-head">
              <div class="min-w-0">
                <strong>{{ mobileTitle(row) }}</strong>
                <p v-if="mobileSubtitle(row)">{{ mobileSubtitle(row) }}</p>
              </div>
            </div>

            <dl class="mobile-detail-grid">
              <template v-for="column in mobileDetailColumns" :key="column.key">
                <div>
                  <dt>{{ column.mobileLabel || column.label }}</dt>
                  <dd>
                    <slot :name="`cell-${column.key}`" :row="row">
                      {{ valueFor(row, column.key) }}
                    </slot>
                  </dd>
                </div>
              </template>
            </dl>

            <div v-if="$slots.actions" class="mobile-record-actions">
              <slot name="actions" :row="row" />
            </div>
          </slot>
        </article>

        <p v-if="rows.length === 0" class="rounded-xl border border-dashed border-slate-200 px-4 py-8 text-center text-sm font-semibold text-slate-500">
          {{ mobileEmptyLabel }}
        </p>
      </div>
    </template>

    <div class="flex flex-col gap-3 border-t border-slate-200 p-4 sm:flex-row sm:items-center sm:justify-between">
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
