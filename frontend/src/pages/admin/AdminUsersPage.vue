<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import { useAdminUserStore } from '../../stores/adminUserStore'
import type { FitnezUser } from '../../types/auth'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'
import { useAutoRefresh } from '../../composables/useAutoRefresh'

const store = useAdminUserStore()
const { loading: initialLoading, run, shimmerStyle } = useDeferredLoading()
const editing = ref<FitnezUser | null>(null)
const showModal = ref(false)
const form = ref({ full_name: '', email: '', phone: '', role: 'member', password: '', password_confirmation: '' })
const saving = ref(false)

function openCreate() {
  editing.value = null
  form.value = { full_name: '', email: '', phone: '', role: 'member', password: '', password_confirmation: '' }
  showModal.value = true
}

function openEdit(user: FitnezUser) {
  editing.value = user
  form.value = {
    full_name: user.full_name,
    email: user.email,
    phone: user.phone || '',
    role: user.role,
    password: '',
    password_confirmation: '',
  }
  showModal.value = true
}

async function save() {
  saving.value = true
  try {
    const payload: Record<string, unknown> = {
      full_name: form.value.full_name,
      email: form.value.email,
      phone: form.value.phone,
      role: form.value.role,
    }
    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }
    if (editing.value) {
      await store.update(editing.value.id, payload)
      window.showFitnezToast('User successfully updated.', 'success')
    } else {
      await store.create(payload)
      window.showFitnezToast('User successfully created.', 'success')
    }
    showModal.value = false
  } catch {
    window.showFitnezToast('Failed to save user.', 'error')
  } finally {
    saving.value = false
  }
}

async function confirmRemove(user: FitnezUser) {
  if (!confirm(`Delete user ${user.full_name}?`)) return
  try {
    await store.remove(user.id)
    window.showFitnezToast('User successfully deleted.', 'success')
  } catch {
    window.showFitnezToast('Failed to delete user.', 'error')
  }
}

function packageBadgeClass(user: FitnezUser) {
  const pkgName = displayPackage(user).toLowerCase()
  if (pkgName.includes('enterprise') || user.role === 'admin') return 'package-enterprise'
  if (pkgName.includes('premium') || pkgName.includes('12 month') || pkgName.includes('6 month')) return 'package-premium'
  return 'package-basic'
}

function displayPackage(user: FitnezUser) {
  const source = user as FitnezUser & {
    membership_package?: { name?: string | null } | null
    package?: { name?: string | null } | null
    membership_type?: string | null
  }

  const name = source.membership_package?.name || source.package?.name || source.membership_type
  if (name) return name
  if (user.role === 'admin') return 'Enterprise'
  if (user.role === 'trainer') return 'Basic'
  return 'Premium'
}

function getUserAvatar(name: string): string | null {
  const clean = name.trim().toLowerCase()
  if (clean.includes('budi santoso')) {
    return 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&q=80&w=120&h=120'
  }
  if (clean.includes('agus pratama')) {
    return 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&q=80&w=120&h=120'
  }
  if (clean.includes('dina wati')) {
    return 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=120&h=120'
  }
  return null
}

function getRandomAvatarBg(name: string): string {
  const clean = name.trim().toLowerCase()
  if (clean.includes('siti')) return 'avatar-lavender'
  if (clean.includes('andi')) return 'avatar-teal'
  if (clean.includes('budi')) return 'avatar-blue'
  return 'avatar-default'
}

function initials(name: string) {
  return name
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map(part => part[0]?.toUpperCase())
    .join('')
}

function setStatusFilter(value: string) {
  store.status = value
  store.page = 1
  store.load()
}

function setPackageFilter(value: string) {
  store.role = value
  store.page = 1
  store.load()
}

function goToPage(page: number) {
  if (page < 1 || page > store.lastPage || page === store.page) return
  store.page = page
  store.load()
}

const visiblePages = computed(() => Array.from({ length: Math.min(store.lastPage, 3) }, (_, index) => index + 1))
const firstItem = computed(() => (store.total === 0 ? 0 : (store.page - 1) * store.perPage + 1))
const lastItem = computed(() => Math.min(store.page * store.perPage, store.total))

async function refreshData() {
  await Promise.all([store.load(), store.loadSummary()])
}

onMounted(() => {
  run(async () => {
    await store.load()
    await store.loadRoles()
    await store.loadSummary()
  })
})
useAutoRefresh(refreshData, 10000)
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="User Management"
    subtitle="Manage access, status, and roles of platform members."
    :sidebar-items="adminSidebarItems"
  >
    <section class="user-management-page">
      <header class="users-header">
        <div class="users-actions">
          <label class="search-box">
            <span class="material-symbols-outlined text-gray-400">search</span>
            <input v-model="store.search" type="search" placeholder="Search users..." @input="store.setSearch(store.search)" />
          </label>

          <button class="add-user-button" type="button" @click="openCreate">
            <span class="material-symbols-outlined text-lg">add</span>
            Add User
          </button>
        </div>
      </header>

      <div class="stat-grid">
        <article class="summary-card">
          <div class="summary-top">
            <span class="summary-icon summary-icon-blue">
              <span class="material-symbols-outlined">group</span>
            </span>
            <h2>Total Members</h2>
          </div>
          <div class="summary-bottom">
            <strong>{{ (store.summary?.total_members ?? 0).toLocaleString('en-US') }}</strong>
            <span :class="['trend', (store.summary?.total_members_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.total_members_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
              <span class="material-symbols-outlined">{{ (store.summary?.total_members_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.total_members_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.total_members_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.total_members_trend ?? 0 }}%
            </span>
          </div>
        </article>

        <article class="summary-card">
          <div class="summary-top">
            <span class="summary-icon summary-icon-gold">
              <span class="material-symbols-outlined">person_add</span>
            </span>
            <h2>New Members<br />This Month</h2>
          </div>
          <div class="summary-bottom">
            <strong>{{ (store.summary?.new_members_this_month ?? 0).toLocaleString('en-US') }}</strong>
            <span :class="['trend', (store.summary?.new_members_this_month_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.new_members_this_month_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
              <span class="material-symbols-outlined">{{ (store.summary?.new_members_this_month_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.new_members_this_month_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.new_members_this_month_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.new_members_this_month_trend ?? 0 }}%
            </span>
          </div>
        </article>

        <article class="summary-card">
          <div class="summary-top">
            <span class="summary-icon summary-icon-red">
              <span class="material-symbols-outlined">person_remove</span>
            </span>
            <h2>Inactive Members</h2>
          </div>
          <div class="summary-bottom">
            <strong>{{ (store.summary?.inactive_members ?? 0).toLocaleString('en-US') }}</strong>
            <span :class="['trend', (store.summary?.inactive_members_trend ?? 0) > 0 ? 'trend-up' : (store.summary?.inactive_members_trend ?? 0) < 0 ? 'trend-down' : 'trend-neutral']">
              <span class="material-symbols-outlined">{{ (store.summary?.inactive_members_trend ?? 0) > 0 ? 'trending_up' : (store.summary?.inactive_members_trend ?? 0) < 0 ? 'trending_down' : 'trending_flat' }}</span>
              {{ (store.summary?.inactive_members_trend ?? 0) > 0 ? '+' : '' }}{{ store.summary?.inactive_members_trend ?? 0 }}%
            </span>
          </div>
        </article>
      </div>

      <section class="users-table-card">
        <div class="filter-row">
          <span class="filter-label">FILTER:</span>
          <div class="filter-buttons">
            <button :class="{ active: store.status === '' }" type="button" @click="setStatusFilter('')">All</button>
            <button :class="{ active: store.status === 'active' }" type="button" @click="setStatusFilter('active')">Active</button>
            <button :class="{ active: store.status === 'inactive' }" type="button" @click="setStatusFilter('inactive')">Inactive</button>
            <i aria-hidden="true" class="divider-line"></i>
            <button :class="{ active: store.role === 'member' }" type="button" @click="setPackageFilter(store.role === 'member' ? '' : 'member')">Premium</button>
          </div>
        </div>

        <SkeletonTable v-if="initialLoading && !store.items.length" :columns="5" :rows="8" :style="shimmerStyle" />

        <div v-else class="responsive-table">
          <table>
            <thead>
              <tr>
                <th>USER</th>
                <th>EMAIL</th>
                <th>PACKAGE TYPE</th>
                <th>STATUS</th>
                <th>ACTION</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="user in store.items" :key="user.id">
                <td>
                  <div class="user-cell">
                    <img v-if="getUserAvatar(user.full_name)" :src="getUserAvatar(user.full_name) || ''" alt="Avatar" class="avatar-img" />
                    <span v-else :class="['avatar-initials', getRandomAvatarBg(user.full_name)]">{{ initials(user.full_name) }}</span>
                    <strong>{{ user.full_name }}</strong>
                  </div>
                </td>
                <td>{{ user.email }}</td>
                <td><span :class="['package-badge', packageBadgeClass(user)]">{{ displayPackage(user) }}</span></td>
                <td><span :class="['status-badge', user.is_active ? 'status-active' : 'status-inactive']">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
                <td>
                  <div class="action-buttons">
                    <button type="button" aria-label="Edit user" title="Edit user" @click="openEdit(user)" class="edit-btn">
                      <span class="material-symbols-outlined text-lg">edit</span>
                    </button>
                    <button class="delete-btn" type="button" aria-label="Delete user" title="Delete user" @click="confirmRemove(user)">
                      <span class="material-symbols-outlined text-lg">delete</span>
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="!store.items.length && !store.loading">
                <td colspan="5" class="empty-cell">No users found.</td>
              </tr>
            </tbody>
          </table>
        </div>

        <footer class="table-footer">
          <span>Showing {{ firstItem }}-{{ lastItem }} of {{ store.total.toLocaleString('en-US') }}</span>
          <div class="pagination">
            <button :disabled="store.page <= 1" type="button" aria-label="Previous page" @click="goToPage(store.page - 1)" class="pagination-arrow">‹</button>
            <button v-for="page in visiblePages" :key="page" :class="{ active: page === store.page }" type="button" @click="goToPage(page)">{{ page }}</button>
            <button :disabled="store.page >= store.lastPage" type="button" aria-label="Next page" @click="goToPage(store.page + 1)" class="pagination-arrow">›</button>
          </div>
        </footer>
      </section>
    </section>

    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-panel">
          <div class="modal-header">
            <h3>{{ editing ? 'Edit User' : 'Add User' }}</h3>
            <button class="modal-close" @click="showModal = false">&times;</button>
          </div>
          <div class="modal-body" style="display: grid; gap: 1rem;">
            <div>
              <label class="label">Full Name</label>
              <input v-model="form.full_name" class="input" placeholder="Full name" />
            </div>
            <div>
              <label class="label">Email</label>
              <input v-model="form.email" class="input" type="email" placeholder="Email" />
            </div>
            <div>
              <label class="label">Phone</label>
              <input v-model="form.phone" class="input" placeholder="Phone number" />
            </div>
            <div>
              <label class="label">Role</label>
              <select v-model="form.role" class="input">
                <option v-for="r in store.roles" :key="r.id" :value="r.name">{{ r.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Password{{ editing ? ' (leave blank if unchanged)' : '' }}</label>
              <input v-model="form.password" class="input" type="password" placeholder="Password" />
            </div>
            <div v-if="form.password">
              <label class="label">Confirm Password</label>
              <input v-model="form.password_confirmation" class="input" type="password" placeholder="Confirm password" />
            </div>
          </div>
          <div class="modal-footer">
            <button class="button button-ghost" @click="showModal = false">Cancel</button>
            <button class="button button-primary" :disabled="saving" @click="save">{{ saving ? 'Saving...' : 'Save' }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </WorkspaceLayout>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap');

.user-management-page {
  font-family: 'Outfit', sans-serif !important;
  color: #0b1c30;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.users-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  flex-wrap: wrap;
  gap: 1.5rem;
}

.users-actions {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}

.search-box {
  display: flex;
  align-items: center;
  gap: 10px;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  height: 48px;
  padding: 0 16px;
  width: 280px;
  transition: border-color 0.2s, box-shadow 0.2s;
  cursor: text;
}

.search-box:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.search-box input {
  border: none !important;
  background: transparent !important;
  font-family: 'Outfit', sans-serif !important;
  font-size: 14px !important;
  font-weight: 500 !important;
  color: #0b1c30 !important;
  width: 100%;
  padding: 0 !important;
  box-shadow: none !important;
}

.search-box input:focus {
  outline: none !important;
}

.add-user-button {
  background: #0058be;
  color: white;
  font-family: 'Outfit', sans-serif !important;
  font-size: 14px;
  font-weight: 700;
  border: none;
  border-radius: 12px;
  height: 48px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  gap: 8px;
  cursor: pointer;
  transition: background 0.2s, transform 0.1s;
}

.add-user-button:hover {
  background: #004ba2;
}

.add-user-button:active {
  transform: scale(0.98);
}

.stat-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.summary-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 24px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  min-height: 150px;
}

.summary-top {
  display: flex;
  align-items: center;
  gap: 12px;
}

.summary-top h2 {
  font-size: 15px;
  font-weight: 700;
  color: #334155;
  margin: 0;
  line-height: 1.25;
}

.summary-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.summary-icon .material-symbols-outlined {
  font-size: 20px;
}

.summary-icon-blue {
  background: #e9f3ff;
  color: #0058be;
}

.summary-icon-gold {
  background: #fdf4e3;
  color: #b45309;
}

.summary-icon-red {
  background: #fef2f2;
  color: #dc2626;
}

.summary-bottom {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  margin-top: 16px;
}

.summary-bottom strong {
  font-size: 32px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1;
}

.trend {
  font-size: 13px;
  font-weight: 700;
  padding: 6px 10px;
  border-radius: 8px;
  display: inline-flex;
  align-items: center;
  gap: 4px;
  line-height: 1;
}

.trend .material-symbols-outlined {
  font-size: 14px;
}

.trend-up {
  background: #eff6ff;
  color: #1d4ed8;
  border: 1px solid #dbeafe;
}

.trend-down {
  background: #fef2f2;
  color: #dc2626;
  border: 1px solid #fee2e2;
}

.trend-neutral {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid #e2e8f0;
}

.trend-goal {
  background: #fef3c7;
  color: #92400e;
  font-size: 12px;
}

.users-table-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
  overflow: hidden;
}

.filter-row {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 24px;
  border-bottom: 1px solid #e2e8f0;
  flex-wrap: wrap;
}

.filter-label {
  font-size: 12px;
  font-weight: 800;
  color: #94a3b8;
  letter-spacing: 0.05em;
}

.filter-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-buttons button {
  background: white;
  border: 1px solid #e2e8f0;
  color: #475569;
  font-family: 'Outfit', sans-serif !important;
  font-size: 13px;
  font-weight: 700;
  border-radius: 8px;
  padding: 6px 16px;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-buttons button:hover {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.filter-buttons button.active {
  background: #e0e7ff;
  color: #3b82f6;
  border-color: #e0e7ff;
}

.divider-line {
  width: 1px;
  height: 16px;
  background: #e2e8f0;
  margin: 0 4px;
  display: inline-block;
}

.responsive-table {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: #f8fafc;
  font-size: 11px;
  font-weight: 800;
  color: #64748b;
  letter-spacing: 0.05em;
  padding: 14px 24px;
  text-align: left;
  border-bottom: 1px solid #e2e8f0;
}

td {
  padding: 14px 24px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  color: #475569;
  font-weight: 600;
  vertical-align: middle;
}

tr:last-child td {
  border-bottom: none;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-cell strong {
  font-size: 14px;
  font-weight: 700;
  color: #0f172a;
}

.avatar-img {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.avatar-initials {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 13px;
  font-weight: 800;
  flex-shrink: 0;
}

.avatar-lavender {
  background: #f3e8ff;
  color: #7e22ce;
}

.avatar-teal {
  background: #ccfbf1;
  color: #0f766e;
}

.avatar-blue {
  background: #dbeafe;
  color: #1d4ed8;
}

.avatar-default {
  background: #f1f5f9;
  color: #475569;
}

.package-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 8px;
  display: inline-block;
  text-transform: capitalize;
}

.package-enterprise {
  background: #fef3c7;
  color: #b45309;
}

.package-premium {
  background: #f3e8ff;
  color: #7e22ce;
}

.package-basic {
  background: #e0f2fe;
  color: #0369a1;
}

.status-badge {
  font-size: 12px;
  font-weight: 700;
  padding: 6px 12px;
  border-radius: 8px;
  display: inline-block;
}

.status-active {
  background: #3b82f6;
  color: white;
}

.status-inactive {
  background: #fee2e2;
  color: #dc2626;
}

.action-buttons {
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-buttons button {
  background: none;
  border: none;
  cursor: pointer;
  padding: 6px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
}

.edit-btn {
  color: #64748b;
}

.edit-btn:hover {
  background: #f1f5f9;
  color: #334155;
}

.delete-btn {
  color: #ef4444;
}

.delete-btn:hover {
  background: #fef2f2;
}

.empty-cell {
  text-align: center;
  color: #94a3b8;
  padding: 40px;
  font-weight: 700;
}

.table-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 24px;
  border-top: 1px solid #e2e8f0;
  font-size: 13px;
  color: #64748b;
  font-weight: 600;
  flex-wrap: wrap;
  gap: 12px;
}

.pagination {
  display: flex;
  align-items: center;
  gap: 6px;
}

.pagination button {
  background: white;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-family: 'Outfit', sans-serif !important;
  font-size: 13px;
  font-weight: 700;
  min-width: 32px;
  height: 32px;
  border-radius: 8px;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.pagination button:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pagination button.active {
  background: #0058be;
  color: white;
  border-color: #0058be;
}

.pagination button:disabled {
  color: #cbd5e1;
  cursor: not-allowed;
  background: #f8fafc;
}

/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-panel {
  background: white;
  border-radius: 20px;
  width: 90%;
  max-width: 480px;
  box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
  overflow: hidden;
  font-family: 'Outfit', sans-serif !important;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 20px 24px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-header h3 {
  font-size: 18px;
  font-weight: 800;
  color: #0b1c30;
  margin: 0;
}

.modal-close {
  background: none;
  border: none;
  font-size: 24px;
  cursor: pointer;
  color: #64748b;
  padding: 0;
  line-height: 1;
}

.modal-body {
  padding: 24px;
}

.label {
  display: block;
  font-weight: 700;
  font-size: 13px;
  color: #475569;
  margin-bottom: 6px;
}

.input {
  width: 100%;
  height: 44px;
  padding: 0 14px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  font-family: 'Outfit', sans-serif !important;
  font-size: 14px;
  color: #0b1c30;
  box-sizing: border-box;
  background: #f8fafc;
  transition: all 0.2s;
}

.input:focus {
  outline: none;
  border-color: #3b82f6;
  background: white;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.1);
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 16px 24px;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
}

.button {
  height: 40px;
  padding: 0 18px;
  border-radius: 10px;
  font-family: 'Outfit', sans-serif !important;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
}

.button-ghost {
  background: transparent;
  border: 1px solid #e2e8f0;
  color: #475569;
}

.button-ghost:hover {
  background: #f1f5f9;
}

.button-primary {
  background: #0058be;
  border: none;
  color: white;
}

.button-primary:hover {
  background: #004ba2;
}

@media (max-width: 1024px) {
  .stat-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .users-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .users-actions {
    flex-direction: column;
    align-items: stretch;
  }
  
  .search-box {
    width: 100%;
  }
  
  .filter-row {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .table-footer {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
  }
  
  .pagination {
    justify-content: center;
  }
}
</style>
