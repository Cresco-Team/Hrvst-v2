<script setup lang="ts">
import { watch, nextTick } from 'vue'
import { useLeafletMap, type MapMarker } from '@/composables/useLeafletMap'

type Props = {
  lat: number
  lng: number
  zoom?: number
  markers?: MapMarker[]
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  zoom: 14,
  markers: () => [],
})

const { container, init, destroy } = useLeafletMap({ zoom: props.zoom })

watch(
  () => [props.lat, props.lng] as const,
  async ([lat, lng]) => {
    await nextTick()
    await init(lat, lng, props.markers)
  },
  { immediate: true },
)

watch(
  () => props.markers,
  async () => {
    await nextTick()
    await init(props.lat, props.lng, props.markers)
  },
  { deep: true },
)

defineExpose({ destroy })
</script>

<template>
  <div ref="container" :class="['h-48 w-full overflow-hidden', props.class]" />
</template>
