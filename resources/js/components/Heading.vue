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
    <header>
        <h1
            :class="
                variant === 'small'
                    ? 'mb-0.5 line-clamp-1 text-base font-medium'
                    : 'text-xl font-semibold tracking-tight'
            "
        >
            {{ title }}
        </h1>
        <p
            v-if="description"
            class="line-clamp-1 text-sm text-muted-foreground"
        >
            {{ description }}
        </p>
    </header>
</template>
