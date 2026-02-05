<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'

interface Category {
    id: number
    name: string
}

defineProps<{
    categories: Category[]
    activeCategory: number | null
}>()

const emit = defineEmits<{
    select: [categoryId: number | null]
}>()

function handleSelect(categoryId: number | null) {
    emit('select', categoryId)
}
</script>

<template>
    <div class="flex items-center gap-2 overflow-x-auto pb-2">
        <Button
            variant="outline"
            size="sm"
            :class="[
                'shrink-0 rounded-full',
                activeCategory === null && 'border-primary bg-primary text-primary-foreground',
            ]"
            @click="handleSelect(null)"
        >
            All Categories
        </Button>
        
        <Button
            v-for="category in categories"
            :key="category.id"
            variant="outline"
            size="sm"
            :class="[
                'shrink-0 rounded-full',
                activeCategory === category.id && 'border-primary bg-primary text-primary-foreground',
            ]"
            @click="handleSelect(category.id)"
        >
            {{ category.name }}
        </Button>

        <!-- Active Filter Indicator -->
        <Badge v-if="activeCategory !== null" variant="secondary" class="ml-2 shrink-0">
            1 filter active
        </Badge>
    </div>
</template>
