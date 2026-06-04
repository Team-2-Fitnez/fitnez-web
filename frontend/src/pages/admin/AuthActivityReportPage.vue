<script setup lang="ts">
import { onMounted } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { useAuthActivityReportStore } from '../../stores/authActivityReportStore'

const store = useAuthActivityReportStore()

function formatDateTime(value?: string | null) {
  if (!value) return '-'
  return new Date(value).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function roleName(user: any) {
  return user?.role?.name || user?.role || 'member'
}

function userDate(user: any, field: string) {
  return user?.[field] || null
}

function statusClass(action: string) {
  if (action === 'LOGIN_SUCCESS' || action === 'USER_CREATED') return 'status status-success'
  if (action === 'LOGIN_FAILED') return 'status status-danger'
  return 'status status-info'
}

function actionLabel(action: string) {
  return action.replaceAll('_', ' ').toLowerCase()
}

onMounted(() => {
  store.loadSummary()
  store.loadLogs()
  store.loadRegistrations()
})
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Login & Registration Report"
    subtitle="View who registered, who logged in successfully, and failed login activity."
    :sidebar-items="adminSidebarItems"
  >
    <div class="report-stat-grid">
      <StatCard label="Total Users" :value="store.summary?.total_registered_users || 0" hint="Registered accounts" />
      <StatCard label="Registered Today" :value="store.summary?.registered_today || 0" hint="New users today" />
      <StatCard label="Login Success Today" :value="store.summary?.login_success_today || 0" hint="Successful login logs" />
      <StatCard label="Login Failed Today" :value="store.summary?.login_failed_today || 0" hint="Failed login logs" />
    </div>

    <FitnezCard class="report-card">
      <div class="report-toolbar">
        <div>
          <p class="eyebrow">Audit Filter</p>
          <h2 class="title-md">Report filters</h2>
          <p class="text-muted">Use one filter set for both login activity and registration records.</p>
        </div>
        <button class="button button-primary toolbar-button" @click="store.refresh">Apply Filter</button>
      </div>

      <div class="filter-grid">
        <label class="filter-field filter-field-wide">
          <span>Search</span>
          <input v-model="store.search" class="form-input" placeholder="Name, email, action..." />
        </label>
        <label class="filter-field">
          <span>Action</span>
          <select v-model="store.actionType" class="form-input">
            <option value="">All actions</option>
            <option value="LOGIN_SUCCESS">Login success</option>
            <option value="LOGIN_FAILED">Login failed</option>
            <option value="USER_CREATED">User created</option>
            <option value="USER_EMAIL_VERIFIED">Email verified</option>
          </select>
        </label>
        <label class="filter-field">
          <span>Role</span>
          <select v-model="store.role" class="form-input">
            <option value="">All roles</option>
            <option value="admin">Admin</option>
            <option value="member">Member</option>
            <option value="trainer">Trainer</option>
          </select>
        </label>
        <label class="filter-field">
          <span>Start date</span>
          <input v-model="store.startDate" class="form-input" type="date" />
        </label>
        <label class="filter-field">
          <span>End date</span>
          <input v-model="store.endDate" class="form-input" type="date" />
        </label>
      </div>
    </FitnezCard>

    <div class="report-grid-two">
      <FitnezCard>
        <div class="table-heading">
          <div>
            <p class="eyebrow">Security Logs</p>
            <h2 class="title-md">Authentication logs</h2>
            <p class="text-muted">Login success, login failure, and account-related audit logs.</p>
          </div>
        </div>

        <div class="responsive-table">
          <table class="data-table report-table">
            <thead>
              <tr>
                <th>Action</th>
                <th>User</th>
                <th>Description</th>
                <th>Time</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in store.logs" :key="row.id">
                <td><span :class="statusClass(row.action_type)">{{ actionLabel(row.action_type) }}</span></td>
                <td>
                  <p class="strong-text">{{ row.user?.full_name || 'Unknown user' }}</p>
                  <p class="text-muted small-text">{{ row.user?.email || '-' }}</p>
                </td>
                <td>{{ row.description || '-' }}</td>
                <td>{{ formatDateTime(row.created_at) }}</td>
              </tr>
              <tr v-if="!store.logs.length && !store.loadingLogs">
                <td colspan="4" class="empty-cell">No authentication logs found.</td>
              </tr>
              <tr v-if="store.loadingLogs">
                <td colspan="4" class="empty-cell">Loading authentication logs...</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mobile-record-list">
          <article v-for="row in store.logs" :key="`log-${row.id}`" class="mobile-record-card">
            <div class="mobile-record-head">
              <span :class="statusClass(row.action_type)">{{ actionLabel(row.action_type) }}</span>
              <small>{{ formatDateTime(row.created_at) }}</small>
            </div>
            <strong>{{ row.user?.full_name || 'Unknown user' }}</strong>
            <p>{{ row.user?.email || '-' }}</p>
            <p class="description-text">{{ row.description || '-' }}</p>
          </article>
          <div v-if="!store.logs.length && !store.loadingLogs" class="soft-empty">No authentication logs found.</div>
          <div v-if="store.loadingLogs" class="soft-empty">Loading authentication logs...</div>
        </div>

        <div class="pager-bar">
          <p>{{ store.logsTotal }} logs · Page {{ store.logsPage }} of {{ store.logsLastPage }}</p>
          <div>
            <button class="button button-ghost button-small" :disabled="store.logsPage <= 1" @click="store.previousLogsPage">Previous</button>
            <button class="button button-ghost button-small" :disabled="store.logsPage >= store.logsLastPage" @click="store.nextLogsPage">Next</button>
          </div>
        </div>
      </FitnezCard>

      <FitnezCard>
        <div class="table-heading">
          <div>
            <p class="eyebrow">Registration</p>
            <h2 class="title-md">Registration records</h2>
            <p class="text-muted">Accounts created in the system, including admin-created and approved manual registration users.</p>
          </div>
        </div>

        <div class="responsive-table">
          <table class="data-table report-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Registered</th>
                <th>Last Login</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in store.registrations" :key="row.id">
                <td>
                  <p class="strong-text">{{ row.full_name }}</p>
                  <p class="text-muted small-text">{{ row.email }}</p>
                </td>
                <td>{{ roleName(row) }}</td>
                <td><span :class="row.is_active ? 'status status-success' : 'status status-danger'">{{ row.is_active ? 'active' : 'inactive' }}</span></td>
                <td>{{ formatDateTime(userDate(row, 'created_at')) }}</td>
                <td>{{ formatDateTime(userDate(row, 'last_login')) }}</td>
              </tr>
              <tr v-if="!store.registrations.length && !store.loadingRegistrations">
                <td colspan="5" class="empty-cell">No registration records found.</td>
              </tr>
              <tr v-if="store.loadingRegistrations">
                <td colspan="5" class="empty-cell">Loading registration records...</td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mobile-record-list">
          <article v-for="row in store.registrations" :key="`registration-${row.id}`" class="mobile-record-card">
            <div class="mobile-record-head">
              <div>
                <strong>{{ row.full_name }}</strong>
                <small>{{ row.email }}</small>
              </div>
              <span :class="row.is_active ? 'status status-success' : 'status status-danger'">{{ row.is_active ? 'active' : 'inactive' }}</span>
            </div>
            <dl class="mobile-detail-grid">
              <div>
                <dt>Role</dt>
                <dd>{{ roleName(row) }}</dd>
              </div>
              <div>
                <dt>Registered</dt>
                <dd>{{ formatDateTime(userDate(row, 'created_at')) }}</dd>
              </div>
              <div>
                <dt>Last Login</dt>
                <dd>{{ formatDateTime(userDate(row, 'last_login')) }}</dd>
              </div>
            </dl>
          </article>
          <div v-if="!store.registrations.length && !store.loadingRegistrations" class="soft-empty">No registration records found.</div>
          <div v-if="store.loadingRegistrations" class="soft-empty">Loading registration records...</div>
        </div>

        <div class="pager-bar">
          <p>{{ store.registrationsTotal }} records · Page {{ store.registrationsPage }} of {{ store.registrationsLastPage }}</p>
          <div>
            <button class="button button-ghost button-small" :disabled="store.registrationsPage <= 1" @click="store.previousRegistrationsPage">Previous</button>
            <button class="button button-ghost button-small" :disabled="store.registrationsPage >= store.registrationsLastPage" @click="store.nextRegistrationsPage">Next</button>
          </div>
        </div>
      </FitnezCard>
    </div>
  </WorkspaceLayout>
</template>

<style scoped>
.report-stat-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 13rem), 1fr));
}

.report-card,
.report-grid-two {
  margin-top: 1.25rem;
}

.report-toolbar,
.table-heading,
.pager-bar,
.mobile-record-head {
  align-items: flex-start;
  display: flex;
  gap: 1rem;
  justify-content: space-between;
}

.toolbar-button {
  flex: 0 0 auto;
}

.filter-grid {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 1fr));
  margin-top: 1.25rem;
}

.filter-field {
  display: grid;
  gap: 0.5rem;
}

.filter-field span {
  font-size: 0.85rem;
  font-weight: 900;
}

.report-grid-two {
  display: grid;
  gap: 1.25rem;
  grid-template-columns: repeat(auto-fit, minmax(min(100%, 34rem), 1fr));
}

.responsive-table {
  margin-top: 1rem;
  overflow-x: auto;
}

.report-table {
  min-width: 820px;
}

.strong-text {
  font-weight: 900;
}

.small-text {
  font-size: 0.8rem;
}

.empty-cell {
  color: var(--color-muted);
  font-weight: 800;
  padding-block: 2.5rem;
  text-align: center;
}

.pager-bar {
  align-items: center;
  border-top: 1px solid rgba(0, 0, 0, 0.10);
  margin-top: 1rem;
  padding-top: 1rem;
}

.pager-bar p {
  color: var(--color-muted);
  font-size: 0.875rem;
  font-weight: 800;
}

.pager-bar div {
  display: flex;
  gap: 0.5rem;
}

.mobile-record-list {
  display: none;
}

.mobile-record-card {
  background: var(--color-white);
  border: 1px solid rgba(0, 0, 0, 0.10);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-panel);
  display: grid;
  gap: 0.75rem;
  padding: 1rem;
}

.mobile-record-card strong {
  font-weight: 900;
}

.mobile-record-card p,
.mobile-record-head small,
.mobile-detail-grid dt {
  color: var(--color-muted);
  font-size: 0.82rem;
  font-weight: 800;
  line-height: 1.6;
}

.description-text {
  background: var(--color-cream);
  border-radius: 1rem;
  padding: 0.75rem;
}

.mobile-detail-grid {
  display: grid;
  gap: 0.75rem;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  margin: 0;
}

.mobile-detail-grid div {
  background: var(--color-cream);
  border-radius: 1rem;
  padding: 0.75rem;
}

.mobile-detail-grid dd {
  font-weight: 900;
  margin: 0.2rem 0 0;
}

.soft-empty {
  background: var(--color-cream);
  border: 1px dashed rgba(0, 0, 0, 0.18);
  border-radius: var(--radius-md);
  color: var(--color-muted);
  font-weight: 800;
  padding: 1rem;
  text-align: center;
}

@media (min-width: 960px) {
  .filter-field-wide {
    grid-column: span 2;
  }
}

@media (max-width: 760px) {
  .report-toolbar,
  .table-heading,
  .pager-bar,
  .mobile-record-head {
    align-items: stretch;
    flex-direction: column;
  }

  .toolbar-button,
  .pager-bar button,
  .pager-bar div {
    width: 100%;
  }

  .responsive-table {
    display: none;
  }

  .mobile-record-list {
    display: grid;
    gap: 0.85rem;
    margin-top: 1rem;
  }

  .mobile-detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
