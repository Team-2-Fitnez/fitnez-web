<script setup lang="ts">
import { onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import { http } from '../../api/http'
import { useAdminUserStore } from '../../stores/adminUserStore'
import type { FitnezUser } from '../../types/auth'

const store = useAdminUserStore()
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
      window.showFitnezToast('User berhasil diperbarui.', 'success')
    } else {
      await store.create(payload)
      window.showFitnezToast('User berhasil dibuat.', 'success')
    }
    showModal.value = false
  } catch {
    window.showFitnezToast('Gagal menyimpan user.', 'error')
  } finally {
    saving.value = false
  }
}

async function confirmRemove(user: FitnezUser) {
  if (!confirm(`Hapus user ${user.full_name}?`)) return
  try {
    await store.remove(user.id)
    window.showFitnezToast('User berhasil dihapus.', 'success')
  } catch {
    window.showFitnezToast('Gagal menghapus user.', 'error')
  }
}

function roleBadge(role: string) {
  const map: Record<string, string> = { admin: 'status-warning', trainer: 'status-info', member: 'status-success' }
  return map[role] || 'status-default'
}

onMounted(() => {
  store.load()
  store.loadRoles()
})
</script>

<template>
  <WorkspaceLayout
    role="admin"
    sidebar-title="Admin"
    title="Users"
    subtitle="Manage admin, trainer, and member accounts."
    :sidebar-items="adminSidebarItems"
  >
    <FitnezCard>
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
          <input v-model="store.search" placeholder="Cari nama atau email..." style="padding: 0.5rem 0.75rem; border: 1px solid var(--color-border); border-radius: 8px; min-width: 200px;" @input="store.setSearch(store.search)" />
          <select v-model="store.role" style="padding: 0.5rem 0.75rem; border: 1px solid var(--color-border); border-radius: 8px;" @change="store.load()">
            <option value="">Semua Role</option>
            <option v-for="r in store.roles" :key="r.id" :value="r.name">{{ r.name }}</option>
          </select>
          <select v-model="store.status" style="padding: 0.5rem 0.75rem; border: 1px solid var(--color-border); border-radius: 8px;" @change="store.load()">
            <option value="">Semua Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
        <a :href="http.url('/admin/export/users')" class="button button-ghost" style="font-size: 0.85rem; text-decoration: none;">Export Excel</a>
        <button class="button button-primary" @click="openCreate">+ Tambah User</button>
      </div>

      <div class="responsive-table">
        <table class="data-table">
          <thead>
            <tr>
              <th>Nama</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in store.items" :key="user.id">
              <td style="font-weight: 700;">{{ user.full_name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.phone || '-' }}</td>
              <td><span :class="['status', roleBadge(user.role)]">{{ user.role }}</span></td>
              <td><span :class="['status', user.is_active ? 'status-success' : 'status-error']">{{ user.is_active ? 'Active' : 'Inactive' }}</span></td>
              <td>
                <div style="display: flex; gap: 0.5rem;">
                  <button class="button button-ghost" style="font-size: 0.8rem; padding: 0.25rem 0.75rem;" @click="openEdit(user)">Edit</button>
                  <button class="button button-ghost" style="font-size: 0.8rem; padding: 0.25rem 0.75rem; color: var(--color-error);" @click="confirmRemove(user)">Hapus</button>
                </div>
              </td>
            </tr>
            <tr v-if="!store.items.length && !store.loading">
              <td colspan="6" class="empty-cell">Tidak ada user ditemukan.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="store.lastPage > 1" style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 1rem;">
        <button :disabled="store.page <= 1" class="button button-ghost" @click="store.page--; store.load()">Prev</button>
        <span style="padding: 0.5rem;">{{ store.page }} / {{ store.lastPage }}</span>
        <button :disabled="store.page >= store.lastPage" class="button button-ghost" @click="store.page++; store.load()">Next</button>
      </div>
    </FitnezCard>

    <Teleport to="body">
      <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
        <div class="modal-panel">
          <div class="modal-header">
            <h3>{{ editing ? 'Edit User' : 'Tambah User' }}</h3>
            <button class="modal-close" @click="showModal = false">&times;</button>
          </div>
          <div class="modal-body" style="display: grid; gap: 1rem;">
            <div>
              <label class="label">Nama Lengkap</label>
              <input v-model="form.full_name" class="input" placeholder="Nama lengkap" />
            </div>
            <div>
              <label class="label">Email</label>
              <input v-model="form.email" class="input" type="email" placeholder="Email" />
            </div>
            <div>
              <label class="label">Phone</label>
              <input v-model="form.phone" class="input" placeholder="No. HP" />
            </div>
            <div>
              <label class="label">Role</label>
              <select v-model="form.role" class="input">
                <option v-for="r in store.roles" :key="r.id" :value="r.name">{{ r.name }}</option>
              </select>
            </div>
            <div>
              <label class="label">Password{{ editing ? ' (kosongkan jika tidak diubah)' : '' }}</label>
              <input v-model="form.password" class="input" type="password" placeholder="Password" />
            </div>
            <div v-if="form.password">
              <label class="label">Konfirmasi Password</label>
              <input v-model="form.password_confirmation" class="input" type="password" placeholder="Konfirmasi password" />
            </div>
          </div>
          <div class="modal-footer">
            <button class="button button-ghost" @click="showModal = false">Batal</button>
            <button class="button button-primary" :disabled="saving" @click="save">{{ saving ? 'Menyimpan...' : 'Simpan' }}</button>
          </div>
        </div>
      </div>
    </Teleport>
  </WorkspaceLayout>
</template>

<style scoped>
.responsive-table { overflow-x: auto; margin: 0 -1px; }
.data-table { width: 100%; border-collapse: collapse; }
.data-table th, .data-table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid var(--color-border); white-space: nowrap; }
.data-table th { font-weight: 700; font-size: 0.8rem; color: var(--color-muted); }
.empty-cell { color: var(--color-muted); font-weight: 800; padding: 2rem; text-align: center; }
.label { display: block; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.25rem; }
.input { width: 100%; padding: 0.6rem 0.75rem; border: 1px solid var(--color-border); border-radius: 8px; font: inherit; box-sizing: border-box; }

.modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.4); display: flex; align-items: center; justify-content: center; z-index: 9999;
}
.modal-panel {
  background: white; border-radius: 16px; width: 90%; max-width: 480px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.15);
}
.modal-header {
  display: flex; justify-content: space-between; align-items: center; padding: 1.25rem 1.5rem; border-bottom: 1px solid var(--color-border);
}
.modal-header h3 { font-size: 1.1rem; font-weight: 900; }
.modal-close { background: none; border: none; font-size: 1.5rem; cursor: pointer; padding: 0; line-height: 1; }
.modal-body { padding: 1.5rem; }
.modal-footer { display: flex; justify-content: flex-end; gap: 0.75rem; padding: 1rem 1.5rem; border-top: 1px solid var(--color-border); }
</style>
