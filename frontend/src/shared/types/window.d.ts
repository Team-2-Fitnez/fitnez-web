export {}

declare global {
  type FitnezToastType = 'success' | 'error' | 'info'

  interface Window {
    showFitnezToast: (message: string, type?: FitnezToastType) => void
  }
}
