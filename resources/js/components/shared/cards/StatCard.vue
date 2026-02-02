<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps({
    title: {
        type: String,
        required: true,
        default: 'Stat Title',
    },
    value: [String, Number],

})
</script>

<template>
    <Card>
        <CardHeader>
            <CardTitle>
                {{  title  }}
            </CardTitle>
        </CardHeader>
        <CardContent>
            {{ value }}
        </CardContent>
    </Card>
</template>