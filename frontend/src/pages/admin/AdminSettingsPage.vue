<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonCard from '../../components/ui/SkeletonCard.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

type PaymentMethod = {
  id: number
  code: string
  type: string
  display_name: string
  bank_name: string | null
  account_number: string | null
  account_name: string | null
  instructions: string | null
  is_active: boolean
}

type PackageItem = {
  id: number
  name: string
  duration_months: number
  price: number
  is_active: boolean
}

type Paginator<T> = {
  data: T[]
  total: number
}

const paymentMethods = ref<PaymentMethod[]>([])
const packages = ref<PackageItem[]>([])
const userTotal = ref(0)
const trainerTotal = ref(0)
const scheduleTotal = ref(0)
const { loading, run, shimmerStyle } = useDeferredLoading()
const error = ref('')

const activePaymentMethods = computed(() => paymentMethods.value.filter(item => item.is_active).length)
const activePackages = computed(() => packages.value.filter(item => item.is_active).length)

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

async function loadSettingsOverview() {
  error.value = ''
  try {
    const [methodsRes, packagesRes, usersRes, trainersRes, schedulesRes] = await Promise.all([
      http.get<PaymentMethod[]>('/manual-payment-methods?limit=50'),
      http.get<PackageItem[]>('/membership-packages?limit=50'),
      http.get<Paginator<unknown>>('/admin/users?per_page=1'),
      http.get<Paginator<unknown>>('/admin/trainers?per_page=1'),
      http.get<Paginator<unknown>>('/admin/schedules?per_page=1'),
    ])

    paymentMethods.value = methodsRes.data || []
    packages.value = packagesRes.data || []
    userTotal.value = usersRes.data?.total || 0
    trainerTotal.value = trainersRes.data?.total || 0
    scheduleTotal.value = schedulesRes.data?.total || 0
    } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load admin settings.'
  }
}

onMounted(() => run(loadSettingsOverview))
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Settings" subtitle="Summary of operational configurations currently active in the database." :sidebar-items="adminSidebarItems">
    <template #default>
      <div v-if="loading" :style="shimmerStyle">
        <SkeletonStatGrid :count="3" />
        <div class="mt-5 grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
          <SkeletonCard heading :lines="4" />
          <SkeletonCard :lines="3" />
        </div>
      </div>
      <template v-else>
      <div class="grid gap-4 md:grid-cols-3">
        <StatCard label="Payment Methods" :value="activePaymentMethods" hint="active" />
        <StatCard label="Membership Packages" :value="activePackages" hint="active" />
        <StatCard label="System Accounts" :value="userTotal" hint="admin, member, trainer" />
      </div>

      <p v-if="error" class="mt-5 rounded-2xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ error }}</p>

      <div v-else class="mt-5 grid gap-5 lg:grid-cols-[1.15fr_0.85fr]">
        <FitnezCard>
          <div class="flex flex-col gap-3 border-b border-black/10 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
              <h2 class="text-2xl font-black">Manual Payment Methods</h2>
              <p class="mt-1 text-sm font-semibold text-black/50">Payment instructions displayed during the member registration flow.</p>
            </div>
            <button class="rounded-xl border border-black/10 px-4 py-2 text-sm font-black text-black/70 hover:bg-black/5" type="button" @click="loadSettingsOverview">
              Reload
            </button>
          </div>

          <div v-if="!paymentMethods.length" class="py-10 text-center text-sm font-bold text-black/45">No payment methods found in the database.</div>
          <div v-else class="mt-4 space-y-3">
            <article v-for="method in paymentMethods" :key="method.id" class="rounded-2xl border border-black/10 p-4">
              <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                <div>
                  <p class="text-lg font-black">{{ method.display_name }}</p>
                  <p class="mt-1 text-xs font-bold uppercase tracking-[0.16em] text-black/40">{{ method.code }} - {{ method.type }}</p>
                </div>
                <span class="w-fit rounded-full px-3 py-1 text-xs font-black" :class="method.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                  {{ method.is_active ? 'Active' : 'Inactive' }}
                </span>
              </div>
              <dl class="mt-4 grid gap-3 text-sm md:grid-cols-2">
                <div>
                  <dt class="font-black text-black/40">Bank</dt>
                  <dd class="mt-1 font-bold">{{ method.bank_name || '-' }}</dd>
                </div>
                <div>
                  <dt class="font-black text-black/40">Account Number</dt>
                  <dd class="mt-1 font-bold">{{ method.account_number || '-' }}</dd>
                </div>
                <div>
                  <dt class="font-black text-black/40">Account Name</dt>
                  <dd class="mt-1 font-bold">{{ method.account_name || '-' }}</dd>
                </div>
                <div>
                  <dt class="font-black text-black/40">Instructions</dt>
                  <dd class="mt-1 font-bold">{{ method.instructions || '-' }}</dd>
                </div>
              </dl>
            </article>
          </div>
        </FitnezCard>

        <div class="space-y-5">
          <FitnezCard>
            <h2 class="text-2xl font-black">Workspace Summary</h2>
            <div class="mt-4 grid gap-3">
              <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-black uppercase text-slate-500">Trainer</p>
                <p class="mt-1 text-3xl font-black">{{ trainerTotal }}</p>
              </div>
              <div class="rounded-2xl bg-slate-50 p-4">
                <p class="text-xs font-black uppercase text-slate-500">Schedule</p>
                <p class="mt-1 text-3xl font-black">{{ scheduleTotal }}</p>
              </div>
            </div>
          </FitnezCard>

          <FitnezCard>
            <h2 class="text-2xl font-black">Active Packages</h2>
            <div v-if="!packages.length" class="py-8 text-center text-sm font-bold text-black/45">No active packages.</div>
            <div v-else class="mt-4 space-y-3">
              <div v-for="item in packages" :key="item.id" class="rounded-2xl border border-black/10 p-4">
                <p class="font-black">{{ item.name }}</p>
                <p class="mt-1 text-sm font-bold text-black/50">{{ item.duration_months }} months - {{ formatCurrency(item.price) }}</p>
              </div>
            </div>
          </FitnezCard>
        </div>
      </div>
      </template>
    </template>
  </WorkspaceLayout>
</template>
