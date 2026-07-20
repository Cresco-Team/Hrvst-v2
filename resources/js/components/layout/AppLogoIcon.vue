<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import logoRaw from '@/../images/app-logo.svg?raw'

defineOptions({
    inheritAttrs: false,
})

type Props = {
    className?: HTMLAttributes['class']
}

defineProps<Props>()
</script>

<template>
    <span
        :class="className"
        v-bind="$attrs"
        class="inline-block"
        v-html="logoRaw"
    ></span>
</template>

<style scoped>
:deep(svg) {
    width: 100%;
    height: 100%;
}
</style>
