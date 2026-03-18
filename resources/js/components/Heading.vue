<script setup lang="ts">
type Props = {
	title: string
	description?: string
	variant?: 'default' | 'small'
}

withDefaults(defineProps<Props>(), {
	variant: 'default',
})
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-8 space-y-0.5'">
        <h1
            :class="
                variant === 'small'
                    ? 'mb-0.5 text-base font-medium'
                    : 'text-xl font-semibold tracking-tight'
            "
        >
            {{ title }}
        </h1>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
