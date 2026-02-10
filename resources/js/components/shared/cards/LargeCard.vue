<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import Card from '@/components/ui/card/Card.vue';
import CardContent from '@/components/ui/card/CardContent.vue';
import CardDescription from '@/components/ui/card/CardDescription.vue';
import CardHeader from '@/components/ui/card/CardHeader.vue';
import CardTitle from '@/components/ui/card/CardTitle.vue';

const props = defineProps({
    title: {
        type: String,
        required: true,
        default: 'Card Title',
    },value: {
        tyle: Number,
        required: true,
        default: 0,
    }, subtext: {
        type: String,
        required: false,
        default: '',
    }, change: {
        type: String,
        required: false,
        default: 0,
    }, icon: {
        type: Object,
        required: false,
        default: null,
    }, trendColor: {
        type: String,
        required: false,
        default: null,
    }, cardClass: {
        type: String,
        required: false,
        default: null,
    }
})
</script>

<template>
    <Card class="gap-2 overflow-hidden hover:shadow-md transition-all" :class="cardClass">
        <CardContent class="flex items-center justify-between">
            <CardDescription>{{ title }}</CardDescription>
            <Badge v-if="change" variant="outline">
                <component :is="icon" :class="trendColor"/>
                <span :class="trendColor">{{ change }}</span>
            </Badge>
        </CardContent>
        <CardHeader>
            <CardTitle class="text-5xl">
                {{ value }}
                <span class="text-muted-foreground font-light text-sm">
                    {{ subtext }}
                </span>
            </CardTitle>
        </CardHeader>
    </Card>
</template>