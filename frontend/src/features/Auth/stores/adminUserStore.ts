import { defineStore } from 'pinia'
import { adminUsersApi, type TableQuery, type AdminUserSummary } from '@/features/Auth/api/adminUsersApi'
import type { FitnezUser } from '@/features/Auth/types/auth'
import type { Role } from '@/shared/types/masterData'

export const useAdminUserStore = defineStore('adminUsers', {
  state: () => ({
    items: [] as FitnezUser[],
    roles: [] as Role[],
    summary: null as AdminUserSummary | null,
    loading: false,
    loadingSummary: false,
    search: '',
    role: '',
    status: '',
    page: 1,
    perPage: 15,
    lastPage: 1,
    total: 0,
  }),

  actions: {
    async load(silent = false) {
      if (!silent) this.loading = true
      try {
        const query: TableQuery = {
          search: this.search,
          role: this.role,
          status: this.status,
          page: this.page,
          per_page: this.perPage,
        }
        const response = await adminUsersApi.list(query)
        this.items = response.data.data
        this.page = response.data.current_page
        this.lastPage = response.data.last_page
        this.total = response.data.total
      } finally {
        if (!silent) this.loading = false
      }
    },

    async loadRoles() {
      const response = await adminUsersApi.roles()
      this.roles = response.data
    },
    async loadSummary(silent = false) {
      if (!silent) this.loadingSummary = true
      try {
        const response = await adminUsersApi.summary()
        this.summary = response.data
      } finally {
        if (!silent) this.loadingSummary = false
      }
    },

    async create(payload: Record<string, unknown>) {
      await adminUsersApi.create(payload)
      await this.load()
    },

    async update(id: number, payload: Record<string, unknown>) {
      await adminUsersApi.update(id, payload)
      await this.load()
    },

    async remove(id: number) {
      await adminUsersApi.remove(id)
      await this.load()
    },

    setSearch(value: string) {
      this.search = value
      this.page = 1
      this.load()
    },
  },
})
