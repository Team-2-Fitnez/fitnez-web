import { computed, reactive } from 'vue'

export type ConfirmationOptions = {
  title?: string
  message?: string
  confirmText?: string
  cancelText?: string
}

type ConfirmationState = Required<ConfirmationOptions> & {
  isOpen: boolean
  resolver: ((value: boolean) => void) | null
}

const state = reactive<ConfirmationState>({
  isOpen: false,
  title: 'Konfirmasi tindakan',
  message: 'Apakah kamu yakin ingin melanjutkan tindakan ini?',
  confirmText: 'Terima',
  cancelText: 'Tolak',
  resolver: null,
})

export function useConfirmation() {
  function ask(options: ConfirmationOptions = {}) {
    state.title = options.title ?? 'Konfirmasi tindakan'
    state.message = options.message ?? 'Apakah kamu yakin ingin melanjutkan tindakan ini?'
    state.confirmText = options.confirmText ?? 'Terima'
    state.cancelText = options.cancelText ?? 'Tolak'
    state.isOpen = true

    return new Promise<boolean>((resolve) => {
      state.resolver = resolve
    })
  }

  function accept() {
    state.resolver?.(true)
    state.resolver = null
    state.isOpen = false
  }

  function reject() {
    state.resolver?.(false)
    state.resolver = null
    state.isOpen = false
  }

  return {
    confirmationState: computed(() => state),
    ask,
    accept,
    reject,
  }
}
