<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Sprout } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Farmer/SupplyController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import type { FarmerSupplyResource, VegetableOptionsByCategory } from '@/types'

interface Props {
    open: boolean
    supply?: FarmerSupplyResource | null
    vegetableOptions?: VegetableOptionsByCategory
}

const props = withDefaults(defineProps<Props>(), { supply: null })

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({
    vegetable_id: '',
    target_month: '',
    estimated_total_weight: '',
})

const isEditMode = computed(() => !!props.supply)
const minMonth = computed(() => new Date().toISOString().slice(0, 7))

function handleSubmit() {
    const options = {
        preserveScroll: true,
        only: ['growingPosts', 'summary'],
        onSuccess: () => {
            emit('update:open', false)
            form.reset()
        },
    }

    if (props.supply) {
        form.put(update(props.supply.id).url, options)
    } else {
        form.post(store().url, options)
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return
        const s = props.supply
        form.vegetable_id = String(s?.vegetable?.id ?? '')
        form.target_month = s?.target_month ?? ''
        form.estimated_total_weight = String(s?.estimated_total_weight ?? '')
        form.clearErrors()
    },
)
</script>

<template>
    <DialogForm
        :open="open"
        :title="isEditMode ? 'Edit Supply' : 'New Supply'"
        :description="
            isEditMode
                ? 'Update your supply details.'
                : 'Register an upcoming harvest.'
        "
        :form="form"
        :submit-label="isEditMode ? 'Update Supply' : 'Register Supply'"
        max-width="2xl"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <Sprout class="size-5 text-primary" />
        </template>

        <div class="space-y-6">
            <div class="space-y-2">
                <Label for="vegetable" class="flex items-center gap-1.5">
                    Vegetable
                    <Badge variant="secondary" class="text-xs font-normal"
                        >Required</Badge
                    >
                </Label>
                <Select v-model="form.vegetable_id" :disabled="isEditMode">
                    <SelectTrigger
                        id="vegetable"
                        :class="{
                            'border-destructive': form.errors.vegetable_id,
                        }"
                    >
                        <SelectValue placeholder="Select a vegetable..." />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectGroup
                            v-for="(vegetables, category) in vegetableOptions"
                            :key="category"
                        >
                            <SelectLabel>{{ category }}</SelectLabel>
                            <SelectItem
                                v-for="v in vegetables"
                                :key="v.id"
                                :value="String(v.id)"
                            >
                                {{ v.name }}
                            </SelectItem>
                        </SelectGroup>
                    </SelectContent>
                </Select>
                <p
                    v-if="form.errors.vegetable_id"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.vegetable_id }}
                </p>
                <p v-else-if="isEditMode" class="text-xs text-muted-foreground">
                    Vegetable cannot be changed after creation
                </p>
            </div>

            <div class="space-y-2">
                <Label for="target_month" class="flex items-center gap-1.5">
                    Target Harvest Month
                    <Badge variant="secondary" class="text-xs font-normal"
                        >Required</Badge
                    >
                </Label>
                <Input
                    id="target_month"
                    v-model="form.target_month"
                    type="month"
                    :min="minMonth"
                    :class="{ 'border-destructive': form.errors.target_month }"
                />
                <p
                    v-if="form.errors.target_month"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.target_month }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Which month do you plan to harvest?
                </p>
            </div>

            <div class="space-y-2">
                <Label for="weight" class="flex items-center gap-1.5">
                    Estimated Total Weight (kg)
                    <Badge variant="secondary" class="text-xs font-normal"
                        >Required</Badge
                    >
                </Label>
                <Input
                    id="weight"
                    v-model.number="form.estimated_total_weight"
                    type="number"
                    step="0.1"
                    min="0.1"
                    placeholder="0.0"
                    :class="{
                        'border-destructive':
                            form.errors.estimated_total_weight,
                    }"
                />
                <p
                    v-if="form.errors.estimated_total_weight"
                    class="text-xs text-destructive"
                >
                    {{ form.errors.estimated_total_weight }}
                </p>
                <p v-else class="text-xs text-muted-foreground">
                    Your best estimate — exact variety weights set on harvest
                    day.
                </p>
            </div>
        </div>
    </DialogForm>
</template>
