<script setup lang="ts">
import type { Component } from 'vue';
import { Button } from './ui/button';
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from './ui/empty';

const props = defineProps<{
        title: string
        description: string
        icon: Component
        button: string
}>()
</script>

<template>
    <Empty class="border border-dashed mx-20">
        <EmptyHeader>
            <EmptyMedia variant="icon">
                <component :is="icon" />
            </EmptyMedia>
            <EmptyTitle>{{ title }}</EmptyTitle>
            <EmptyDescription>
                {{ description }}
            </EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
            <Button size="sm" >
                {{ button }}
            </Button>
        </EmptyContent>
    </Empty>
</template>