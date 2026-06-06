<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import WorkspaceLayout from '../../components/layout/WorkspaceLayout.vue'
import { adminSidebarItems } from '../../components/layout/sidebarItems'
import FitnezCard from '../../components/ui/FitnezCard.vue'
import StatCard from '../../components/ui/StatCard.vue'
import { http } from '../../api/http'
import SkeletonStatGrid from '../../components/ui/skeleton/SkeletonStatGrid.vue'
import SkeletonTable from '../../components/ui/SkeletonTable.vue'
import { useDeferredLoading } from '../../composables/useDeferredLoading'

type MembershipPackage = {
  id: number
  code: string
  name: string
  duration_months: number
  price: number
  free_class_access: boolean
  benefits: string[]
  is_active: boolean
}

const packages = ref<MembershipPackage[]>([])
const error = ref('')
const { loading, run, shimmerStyle } = useDeferredLoading()

const activePackages = computed(() => packages.value.filter(item => item.is_active).length)
const classAccessPackages = computed(() => packages.value.filter(item => item.free_class_access).length)
const lowestPrice = computed(() => {
  if (!packages.value.length) return 0
  return Math.min(...packages.value.map(item => Number(item.price || 0)))
})

function formatCurrency(value: number) {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
  }).format(value)
}

async function loadPackages() {
  error.value = ''
  try {
    const response = await http.get<MembershipPackage[]>('/membership-packages?limit=50')
    packages.value = response.data || []
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Failed to load membership packages.'
  }
}

onMounted(() => run(loadPackages))
</script>

<template>
  <WorkspaceLayout role="admin" sidebar-title="Admin" title="Packages" subtitle="Monitor active membership packages, duration, prices, and class access." :sidebar-items="adminSidebarItems">
    <template #default>
      <div v-if="loading && !packages.length" :style="shimmerStyle">
        <SkeletonStatGrid :count="3" />
        <div class="mt-5"><SkeletonTable :columns="6" :rows="6" /></div>
      </div>
      <template v-else>
      <div class="grid gap-4 md:grid-cols-3">
        <StatCard label="Active Packages" :value="activePackages" hint="available for members" />
        <StatCard label="Class Access" :value="classAccessPackages" hint="packages with free class access" />
        <StatCard label="Lowest Price" :value="formatCurrency(lowestPrice)" hint="starting from" />
      </div>

      <FitnezCard class="mt-5">
        <div class="flex flex-col gap-3 border-b border-black/10 pb-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h2 class="text-2xl font-black">Membership Package List</h2>
            <p class="mt-1 text-sm font-semibold text-black/50">Data is retrieved directly from the membership package database.</p>
          </div>
          <button class="rounded-xl border border-black/10 px-4 py-2 text-sm font-black text-black/70 hover:bg-black/5" type="button" @click="loadPackages">
            Reload
          </button>
        </div>

        <p v-if="error" class="mt-4 rounded-2xl bg-red-50 px-4 py-3 text-sm font-bold text-red-700">{{ error }}</p>
        <p v-else-if="!packages.length" class="py-10 text-center text-sm font-bold text-black/45">No membership packages in the database.</p>

        <div v-else class="fitnez-desktop-only mt-4 overflow-x-auto">
          <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
              <tr>
                <th class="px-4 py-3">Package</th>
                <th class="px-4 py-3">Duration</th>
                <th class="px-4 py-3">Price</th>
                <th class="px-4 py-3">Class Access</th>
                <th class="px-4 py-3">Benefits</th>
                <th class="px-4 py-3">Status</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in packages" :key="item.id" class="border-t border-black/5">
                <td class="px-4 py-4">
                  <p class="font-black text-black">{{ item.name }}</p>
                  <p class="mt-1 text-xs font-bold text-black/40">{{ item.code }}</p>
                </td>
                <td class="px-4 py-4 font-bold">{{ item.duration_months }} months</td>
                <td class="px-4 py-4 font-black">{{ formatCurrency(item.price) }}</td>
                <td class="px-4 py-4 font-bold">{{ item.free_class_access ? 'Included' : 'Not included' }}</td>
                <td class="px-4 py-4 text-black/60">
                  <span v-for="benefit in item.benefits" :key="benefit" class="mr-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">
                    {{ benefit }}
                  </span>
                </td>
                <td class="px-4 py-4">
                  <span class="rounded-full px-3 py-1 text-xs font-black" :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                    {{ item.is_active ? 'Active' : 'Inactive' }}
                  </span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="packages.length" class="mobile-record-list mt-4">
          <article v-for="item in packages" :key="`package-card-${item.id}`" class="mobile-record-card">
            <div class="mobile-record-head">
              <div>
                <strong>{{ item.name }}</strong>
                <p>{{ item.code }}</p>
              </div>
              <span class="rounded-full px-3 py-1 text-xs font-black" :class="item.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'">
                {{ item.is_active ? 'Active' : 'Inactive' }}
              </span>
            </div>
            <dl class="mobile-detail-grid">
              <div>
                <dt>Duration</dt>
                <dd>{{ item.duration_months }} months</dd>
              </div>
              <div>
                <dt>Price</dt>
                <dd>{{ formatCurrency(item.price) }}</dd>
              </div>
              <div>
                <dt>Class Access</dt>
                <dd>{{ item.free_class_access ? 'Included' : 'Not included' }}</dd>
              </div>
              <div>
                <dt>Benefits</dt>
                <dd>{{ item.benefits?.length ? item.benefits.join(', ') : '-' }}</dd>
              </div>
            </dl>
          </article>
        </div>
      </FitnezCard>
      </template>
    </template>
  </WorkspaceLayout>
</template>
