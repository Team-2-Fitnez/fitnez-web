<script setup lang="ts">
import { nextTick, onMounted, onUnmounted } from 'vue'
import CookieConsent from '@/shared/components/privacy/CookieConsent.vue'
import ConfirmationDialog from '@/shared/components/ui/ConfirmationDialog.vue'
import { useConfirmation } from '@/shared/composables/useConfirmation'

let tableObserver: MutationObserver | null = null
let scheduledFrame = 0

const { confirmationState, accept, reject } = useConfirmation()

function labelResponsiveTables(root: ParentNode = document) {
  root.querySelectorAll('table').forEach((table) => {
    if (table.closest('[data-no-responsive-cards]')) return

    table.classList.add('responsive-card-table')

    const headers = Array.from(table.querySelectorAll('thead th')).map((header, index) => {
      const label = header.textContent?.replace(/\s+/g, ' ').trim()
      return label || (index === table.querySelectorAll('thead th').length - 1 ? 'Actions' : `Column ${index + 1}`)
    })

    table.querySelectorAll('tbody tr').forEach((row) => {
      Array.from(row.children).forEach((cell, index) => {
        if (!(cell instanceof HTMLTableCellElement)) return
        if (cell.hasAttribute('data-label')) return
        cell.setAttribute('data-label', headers[index] || `Column ${index + 1}`)
      })
    })
  })
}

function scheduleTableLabeling() {
  if (scheduledFrame) return
  scheduledFrame = window.requestAnimationFrame(() => {
    scheduledFrame = 0
    labelResponsiveTables()
  })
}

onMounted(() => {
  nextTick(scheduleTableLabeling)

  const root = document.getElementById('app')
  if (!root) return

  tableObserver = new MutationObserver(scheduleTableLabeling)
  tableObserver.observe(root, { childList: true, subtree: true })
})

onUnmounted(() => {
  if (scheduledFrame) window.cancelAnimationFrame(scheduledFrame)
  tableObserver?.disconnect()
})
</script>

<template>
  <RouterView />
  <CookieConsent />
  <ConfirmationDialog
    :model-value="confirmationState.isOpen"
    :title="confirmationState.title"
    :message="confirmationState.message"
    :confirm-text="confirmationState.confirmText"
    :cancel-text="confirmationState.cancelText"
    @confirm="accept"
    @cancel="reject"
    @update:model-value="(value) => { if (!value) reject() }"
  />
</template>
