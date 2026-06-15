<template>
  <Teleport to="body">
    <div v-if="modelValue" class="confirmation-backdrop" role="presentation" @click.self="cancel">
      <section class="confirmation-dialog" role="dialog" aria-modal="true" :aria-labelledby="titleId">
        <header class="confirmation-dialog__header">
          <h2 :id="titleId">{{ title }}</h2>
          <p v-if="message">{{ message }}</p>
        </header>

        <footer class="confirmation-dialog__actions">
          <button type="button" class="confirmation-dialog__secondary" @click="cancel">
            {{ cancelText }}
          </button>
          <button type="button" class="confirmation-dialog__primary" @click="confirm">
            {{ confirmText }}
          </button>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
withDefaults(defineProps<{
  modelValue: boolean
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
}>(), {
  title: 'Konfirmasi tindakan',
  message: '',
  confirmText: 'Terima',
  cancelText: 'Tolak',
})

const emit = defineEmits<{
  (event: 'update:modelValue', value: boolean): void
  (event: 'confirm'): void
  (event: 'cancel'): void
}>()

const titleId = `confirmation-dialog-${Math.random().toString(36).slice(2)}`

function confirm() {
  emit('confirm')
  emit('update:modelValue', false)
}

function cancel() {
  emit('cancel')
  emit('update:modelValue', false)
}
</script>

<style scoped>
.confirmation-backdrop {
  align-items: center;
  background: rgba(15, 23, 42, 0.55);
  display: flex;
  inset: 0;
  justify-content: center;
  padding: 1rem;
  position: fixed;
  z-index: 1000;
}

.confirmation-dialog {
  background: #fffaf0;
  border-radius: 1rem;
  box-shadow: 0 24px 64px rgba(15, 23, 42, 0.25);
  max-width: 28rem;
  padding: 1.25rem;
  width: 100%;
}

.confirmation-dialog__header h2 {
  color: #0f2a44;
  font-size: 1.15rem;
  font-weight: 800;
  margin: 0 0 .5rem;
}

.confirmation-dialog__header p {
  color: #475569;
  line-height: 1.5;
  margin: 0;
}

.confirmation-dialog__actions {
  display: flex;
  gap: .75rem;
  justify-content: flex-end;
  margin-top: 1.25rem;
}

.confirmation-dialog__primary,
.confirmation-dialog__secondary {
  border: 0;
  border-radius: .75rem;
  cursor: pointer;
  font-weight: 700;
  padding: .65rem 1rem;
}

.confirmation-dialog__primary {
  background: #f97316;
  color: white;
}

.confirmation-dialog__secondary {
  background: #e2e8f0;
  color: #0f2a44;
}
</style>
