<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import logoRaw from '../../images/app-logo.svg?raw' // Adjust path as needed

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
        v-html="logoRaw"
        class="inline-block"
    ></span>
</template>

<style scoped>
/* This ensures the injected SVG inherits dimensions and colors */
:deep(svg) {
    width: 100%;
    height: 100%;
}
</style>