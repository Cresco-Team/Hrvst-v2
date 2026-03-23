import { router } from '@inertiajs/vue3'
import { ref } from 'vue'
import index from '@/routes/admin/index'
import type { PendingDealer, PendingFarmer } from '@/types/resources/profile'

type ApprovalState = 'idle' | 'loading' | 'error'

export function usePendingApprovals() {
  const farmers = ref<PendingFarmer[]>([])
  const dealers = ref<PendingDealer[]>([])
  const state = ref<ApprovalState>('idle')
  const error = ref<string | null>(null)

  async function fetch() {
    state.value = 'loading'
    error.value = null

    try {
      const [farmersRes, dealersRes] = await Promise.all([
        window.fetch(index.farmers.api.pending.url()),
        window.fetch(index.dealers.api.pending.url()),
      ])

      if (!farmersRes.ok || !dealersRes.ok) {
        throw new Error('Failed to load pending approvals.')
      }

      farmers.value = await farmersRes.json()
      dealers.value = await dealersRes.json()
      state.value = 'idle'
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Unknown error'
      state.value = 'error'
    }
  }

  function approveFarmer(id: number) {
    const snapshot = [...farmers.value]
    farmers.value = farmers.value.filter((f) => f.id !== id)

    router.post(
      index.farmers.approve.url(id),
      {},
      {
        preserveScroll: true,
        onError: () => {
          farmers.value = snapshot
        },
      },
    )
  }

  function rejectFarmer(id: number) {
    const snapshot = [...farmers.value]
    farmers.value = farmers.value.filter((f) => f.id !== id)

    router.post(
      index.farmers.reject.url(id),
      {},
      {
        preserveScroll: true,
        onError: () => {
          farmers.value = snapshot
        },
      },
    )
  }

  function approveDealer(id: number) {
    const snapshot = [...dealers.value]
    dealers.value = dealers.value.filter((d) => d.id !== id)

    router.post(
      index.dealers.approve.url(id),
      {},
      {
        preserveScroll: true,
        onError: () => {
          dealers.value = snapshot
        },
      },
    )
  }

  function rejectDealer(id: number) {
    const snapshot = [...dealers.value]
    dealers.value = dealers.value.filter((d) => d.id !== id)

    router.post(
      index.dealers.reject.url(id),
      {},
      {
        preserveScroll: true,
        onError: () => {
          dealers.value = snapshot
        },
      },
    )
  }

  return {
    farmers,
    dealers,
    state,
    error,
    fetch,
    approveFarmer,
    rejectFarmer,
    approveDealer,
    rejectDealer,
  }
}
