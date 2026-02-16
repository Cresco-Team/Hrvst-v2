<script setup lang="ts">
import DialogForm from '@/components/DialogForm.vue';
import { Sprout } from 'lucide-vue-next';


</script>

<template>
    <DialogForm
        :open="open"
        title="Create Offering Post"
        description="Create offering post"
        :is-submitting="createForm.processing"
        submit-label="Post Offering"
        @update:open="createDialogOpen = $event"
        @submit="handleSubmit"
    >
        <template #icon>
            <Sprout class="size-5 text-primary" />
        </template>

        <div class="flex flex-col gap-5">
            <div>
                <div class="flex flex-col gap-2">
                    <Label for="create_variety_id" class="flex items-center gap-1.5">
                    Variety
                    <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Select v-model="createForm.variety_id">
                    <SelectTrigger 
                        id="create_variety_id"
                        :class="{ 'border-destructive': createForm.errors.variety_id }"
                    >
                        <SelectValue placeholder="Select a variety..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup 
                        v-for="(varieties, category) in varietyOptions" 
                        :key="category"
                        >
                        <SelectLabel>{{ category }}</SelectLabel>
                        <SelectItem
                            v-for="variety in varieties"
                            :key="variety.id"
                            :value="variety.id.toString()"
                        >
                            {{ variety.name }}
                        </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                    </Select>
                    <p v-if="createForm.errors.variety_id" class="text-xs text-destructive">
                    {{ createForm.errors.variety_id }}
                    </p>
                    <p v-else class="text-xs text-muted-foreground">
                    Choose the variety you're offering
                    </p>
                </div>
            </div>

        </div>
    </DialogForm>
</template>