import { defineStore } from 'pinia'
import { adminAuthActivityReportApi } from '../api/adminAuthActivityReportApi'
import type { AuthActivitySummary, SystemLog } from '../types/adminAuthActivityReport'
import type { FitnezUser } from '../types/auth'

export const useAuthActivityReportStore = defineStore('authActivityReport', {
  state: () => ({
    summary: null as AuthActivitySummary | null,
    logs: [] as SystemLog[],
    registrations: [] as FitnezUser[],
    loadingSummary: false,
    loadingLogs: false,
    loadingRegistrations: false,
    search: '',
    actionType: '',
    role: '',
    startDate: '',
    endDate: '',
    logsPage: 1,
    logsLastPage: 1,
    logsTotal: 0,
    registrationsPage: 1,
    registrationsLastPage: 1,
    registrationsTotal: 0,
    perPage: 15,
  }),

  actions: {
    async loadSummary() {
      this.loadingSummary = true

      try {
        const response = await adminAuthActivityReportApi.summary()
        this.summary = response.data
      } finally {
        this.loadingSummary = false
      }
    },

    async loadLogs() {
      this.loadingLogs = true

      try {
        const response = await adminAuthActivityReportApi.logs({
          search: this.search,
          action_type: this.actionType,
          role: this.role,
          start_date: this.startDate,
          end_date: this.endDate,
          page: this.logsPage,
          per_page: this.perPage,
        })

        this.logs = response.data.data
        this.logsPage = response.data.current_page
        this.logsLastPage = response.data.last_page
        this.logsTotal = response.data.total
      } finally {
        this.loadingLogs = false
      }
    },

    async loadRegistrations() {
      this.loadingRegistrations = true

      try {
        const response = await adminAuthActivityReportApi.registrations({
          search: this.search,
          role: this.role,
          start_date: this.startDate,
          end_date: this.endDate,
          page: this.registrationsPage,
          per_page: this.perPage,
        })

        this.registrations = response.data.data
        this.registrationsPage = response.data.current_page
        this.registrationsLastPage = response.data.last_page
        this.registrationsTotal = response.data.total
      } finally {
        this.loadingRegistrations = false
      }
    },

    refresh() {
      this.logsPage = 1
      this.registrationsPage = 1
      this.loadSummary()
      this.loadLogs()
      this.loadRegistrations()
    },

    nextLogsPage() {
      if (this.logsPage >= this.logsLastPage) return
      this.logsPage += 1
      this.loadLogs()
    },

    previousLogsPage() {
      if (this.logsPage <= 1) return
      this.logsPage -= 1
      this.loadLogs()
    },

    nextRegistrationsPage() {
      if (this.registrationsPage >= this.registrationsLastPage) return
      this.registrationsPage += 1
      this.loadRegistrations()
    },

    previousRegistrationsPage() {
      if (this.registrationsPage <= 1) return
      this.registrationsPage -= 1
      this.loadRegistrations()
    },
  },
})
