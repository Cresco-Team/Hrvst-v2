import { Clock, Moon, Sun, Sunset } from 'lucide-vue-next'
import type { Component } from 'vue'
import type { PostTimeSlot } from '@/types/marketplace'

interface TimeSlotConfig {
  icon: Component
  color: string
}

const TIME_SLOT_CONFIG: Record<PostTimeSlot, TimeSlotConfig> = {
  morning: {
    icon: Sun,
    color: 'text-amber-600',
  },
  afternoon: {
    icon: Sunset,
    color: 'text-orange-600',
  },
  evening: {
    icon: Moon,
    color: 'text-indigo-600',
  },
}

const FALLBACK: TimeSlotConfig = {
  icon: Clock,
  color: 'text-muted-foreground',
}

export function useTimeSlot() {
  function getConfig(slot: PostTimeSlot | null | undefined): TimeSlotConfig {
    if (!slot) return FALLBACK
    return TIME_SLOT_CONFIG[slot] ?? FALLBACK
  }

  return { getConfig }
}
