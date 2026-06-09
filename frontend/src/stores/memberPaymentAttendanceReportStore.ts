import { defineStore } from 'pinia'
import { memberPaymentAttendanceReportApi } from '../api/memberPaymentAttendanceReportApi'
import type {
  MemberPaymentAttendanceSummary,
  MemberReportAttendance,
  MemberReportPayment,
} from '../types/memberPaymentAttendanceReport'

export const useMemberPaymentAttendanceReportStore = defineStore('memberPaymentAttendanceReport', {
  state: () => ({
    summary: null as MemberPaymentAttendanceSummary | null,
    payments: [] as MemberReportPayment[],
    attendance: [] as MemberReportAttendance[],
    loadingSummary: false,
    loadingPayments: false,
    loadingAttendance: false,
    search: '',
    paymentStatus: '',
    paymentType: '',
    attendanceType: '',
    startDate: '',
    endDate: '',
    paymentsPage: 1,
    paymentsLastPage: 1,
    paymentsTotal: 0,
    attendancePage: 1,
    attendanceLastPage: 1,
    attendanceTotal: 0,
    perPage: 15,
  }),

  actions: {
    async loadSummary() {
      this.loadingSummary = true

      try {
        const response = await memberPaymentAttendanceReportApi.summary()
        this.summary = response.data
      } finally {
        this.loadingSummary = false
      }
    },

    async loadPayments() {
      this.loadingPayments = true

      try {
        const response = await memberPaymentAttendanceReportApi.payments({
          search: this.search,
          payment_status: this.paymentStatus,
          payment_type: this.paymentType,
          start_date: this.startDate,
          end_date: this.endDate,
          page: this.paymentsPage,
          per_page: this.perPage,
        })

        this.payments = response.data.data
        this.paymentsPage = response.data.current_page
        this.paymentsLastPage = response.data.last_page
        this.paymentsTotal = response.data.total
      } finally {
        this.loadingPayments = false
      }
    },

    async loadAttendance() {
      this.loadingAttendance = true

      try {
        const response = await memberPaymentAttendanceReportApi.attendance({
          search: this.search,
          attendance_type: this.attendanceType,
          start_date: this.startDate,
          end_date: this.endDate,
          page: this.attendancePage,
          per_page: this.perPage,
        })

        this.attendance = response.data.data
        this.attendancePage = response.data.current_page
        this.attendanceLastPage = response.data.last_page
        this.attendanceTotal = response.data.total
      } finally {
        this.loadingAttendance = false
      }
    },

    refresh() {
      this.paymentsPage = 1
      this.attendancePage = 1
      this.loadSummary()
      this.loadPayments()
      this.loadAttendance()
    },

    nextPaymentsPage() {
      if (this.paymentsPage >= this.paymentsLastPage) return
      this.paymentsPage += 1
      this.loadPayments()
    },

    previousPaymentsPage() {
      if (this.paymentsPage <= 1) return
      this.paymentsPage -= 1
      this.loadPayments()
    },

    nextAttendancePage() {
      if (this.attendancePage >= this.attendanceLastPage) return
      this.attendancePage += 1
      this.loadAttendance()
    },

    previousAttendancePage() {
      if (this.attendancePage <= 1) return
      this.attendancePage -= 1
      this.loadAttendance()
    },
  },
})
