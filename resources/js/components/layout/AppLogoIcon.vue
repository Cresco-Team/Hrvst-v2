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
    <!-- logoRaw is bundled at build time via Vite's `?raw` import — a static
         asset from the repo, never derived from user or API input — so
         v-html carries no XSS risk here. -->
    <!-- eslint-disable vue/no-v-html -->
    <span
        :class="className"
        v-bind="$attrs"
        class="inline-block"
        v-html="logoRaw"
    ></span>
    <!-- eslint-enable vue/no-v-html -->
</template>

<style scoped>
:deep(svg) {
    width: 100%;
    height: 100%;
}
</style>
