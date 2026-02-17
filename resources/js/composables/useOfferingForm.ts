import { useForm } from '@inertiajs/vue3'
import { Offering } from '@/types/farmer/garden'

interface OfferingFormData {
  variety_id: number | null
  image: File | null
  weight_kg: number
  asking_price: number
  expiration_date: string
}

export function useOfferingForm(offering: Offering | null = null) {
  const form = useForm<OfferingFormData>({
    variety_id: offering?.variety.id ?? null,
    image: null,
    weight_kg: offering?.weight_kg ?? 0,
    asking_price: offering?.asking_price ?? 0,
    expiration_date: offering?.expiration_date ?? '',
  })

  function reset() {
    form.reset()
    form.clearErrors()
  }

  return {
    form,
    reset,
  }
}
