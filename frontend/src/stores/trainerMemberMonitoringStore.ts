import { defineStore } from 'pinia'
import { trainerMonitoringApi } from '../api/trainerMonitoringApi'
import type {
  TrainerMemberFitnessDetail,
  TrainerMonitoringMember,
  TrainerMonitoringSummary,
} from '../types/trainerMonitoring'

export const useTrainerMemberMonitoringStore = defineStore('trainerMemberMonitoring', {
  state: () => ({
    summary: null as TrainerMonitoringSummary | null,
    members: [] as TrainerMonitoringMember[],
    selected: null as TrainerMemberFitnessDetail | null,
    loadingSummary: false,
    loadingMembers: false,
    loadingDetail: false,
    search: '',
    page: 1,
    perPage: 10,
    lastPage: 1,
    total: 0,
  }),

  actions: {
    async loadSummary() {
      this.loadingSummary = true

      try {
        const response = await trainerMonitoringApi.summary()
        this.summary = response.data
      } finally {
        this.loadingSummary = false
      }
    },

    async loadMembers() {
      this.loadingMembers = true

      try {
        const response = await trainerMonitoringApi.members({
          search: this.search,
          page: this.page,
          per_page: this.perPage,
        })

        this.members = response.data.data
        this.page = response.data.current_page
        this.lastPage = response.data.last_page
        this.total = response.data.total

        const firstMember = this.members[0]
        if (!this.selected && firstMember) {
          await this.loadDetail(firstMember.id)
        }
      } finally {
        this.loadingMembers = false
      }
    },

    async loadDetail(memberId: number) {
      this.loadingDetail = true

      try {
        const response = await trainerMonitoringApi.detail(memberId)
        this.selected = response.data
      } finally {
        this.loadingDetail = false
      }
    },

    setSearch(value: string) {
      this.search = value
      this.page = 1
      this.loadMembers()
    },

    nextPage() {
      if (this.page >= this.lastPage) return
      this.page += 1
      this.loadMembers()
    },

    previousPage() {
      if (this.page <= 1) return
      this.page -= 1
      this.loadMembers()
    },
  },
})
