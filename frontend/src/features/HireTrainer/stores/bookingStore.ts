import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { bookingsApi, type PublicTrainer } from '@/features/HireTrainer/api/bookingsApi'
import type { TrainerBooking } from '@/shared/types/masterData'

export const useBookingStore = defineStore('bookings', () => {
  const trainers = ref<PublicTrainer[]>([])
  const bookings = ref<TrainerBooking[]>([])
  const pendingBookings = ref<TrainerBooking[]>([])
  const loading = ref(false)
  const trainersLoading = ref(false)
  const page = ref(1)
  const lastPage = ref(1)

  const pendingPaymentBookings = computed(() =>
    bookings.value.filter(b => b.status === 'pending_payment')
  )
  const activeBookings = computed(() =>
    bookings.value.filter(b => b.status === 'confirmed')
  )

  async function loadTrainers() {
    trainersLoading.value = true
    try {
      const response = await bookingsApi.publicTrainers()
      trainers.value = response.data
    } finally {
      trainersLoading.value = false
    }
  }

  async function loadBookings() {
    loading.value = true
    try {
      const response = await bookingsApi.list(page.value)
      bookings.value = response.data.data
      page.value = response.data.current_page
      lastPage.value = response.data.last_page
    } finally {
      loading.value = false
    }
  }

  async function createBooking(payload: Record<string, unknown>) {
    const res = await bookingsApi.create(payload as any)
    page.value = 1
    await loadBookings()
    return res
  }

  async function uploadPaymentProof(id: number, file: File) {
    await bookingsApi.uploadProof(id, file)
    await loadBookings()
  }

  async function updateStatus(id: number, status: string) {
    await bookingsApi.updateStatus(id, status)
    await loadBookings()
  }

  async function loadPendingPayments() {
    const res = await bookingsApi.pendingPayments(page.value)
    pendingBookings.value = res.data.data
  }

  async function confirmPayment(id: number) {
    await bookingsApi.confirmPayment(id)
    await loadPendingPayments()
    await loadBookings()
  }

  async function rejectPayment(id: number, reason: string) {
    await bookingsApi.rejectPayment(id, reason)
    await loadPendingPayments()
    await loadBookings()
  }

  async function fetchSessionDates(id: number) {
    const res = await bookingsApi.sessionDates(id)
    return res.data
  }

  return {
    trainers, bookings, pendingBookings, loading, trainersLoading, page, lastPage,
    pendingPaymentBookings, activeBookings,
    loadTrainers, loadBookings, createBooking, uploadPaymentProof,
    updateStatus, loadPendingPayments, confirmPayment, rejectPayment, fetchSessionDates,
  }
})
