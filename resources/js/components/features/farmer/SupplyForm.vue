<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { PackageCheck, Plus, Trash2 } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { update } from '@/actions/App/Http/Controllers/Farmer/SupplyController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select'
import type { FarmerSupplyDataFixed, VarietyOptionsByVegetable } from '@/types'

interface Props {
    open: boolean
    supply: FarmerSupplyDataFixed | null
    varietyOptions?: VarietyOptionsByVegetable
}

const props = withDefaults(defineProps<Props>(), { supply: null })

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

const form = useForm({
    scheduled_date: '',
    time_slot: '',
    estimated_total_weight: '',
    items: [] as Array<{
        _key: number
        id: number | null
        variety_id: string
        quantity_kg: string
        status: string
    }>,
})

const relevantVarieties = computed(() => {
    if (!props.supply?.vegetable?.name || !props.varietyOptions) return []
    return props.varietyOptions[props.supply.vegetable.name] ?? []
})

function toInputDate(dateStr: string | null | undefined): string {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10)
}

function isFulfilled(item: (typeof form.items)[number]): boolean {
    return item.status === 'fulfilled'
}

function addItem(): void {
    form.items.push({
        _key: nextKey(),
        id: null,
        variety_id: '',
        quantity_kg: '',
        status: 'ongoing',
    })
}

function removeItem(index: number): void {
    form.items.splice(index, 1)
}

function handleSubmit(): void {
    if (!props.supply) return

    form.put(update(props.supply.id).url, {
        preserveScroll: true,
        only: ['supplies', 'summary'],
        onSuccess: () => {
            emit('update:open', false)
            form.reset()
        },
    })
}

watch(
    () => [props.open, props.supply] as const,
    ([isOpen, s]) => {
        if (!isOpen || !s) return

        form.scheduled_date = toInputDate(s.scheduled_date)
        form.time_slot = s.time_slot ?? ''
        form.estimated_total_weight = String(s.estimated_total_weight ?? '')
        form.items = (s.post_items ?? []).map((item) => ({
            _key: nextKey(),
            id: item.id,
            variety_id: String((item as any).variety_id ?? ''),
            quantity_kg: String(item.quantity_kg ?? ''),
            status: (item as any).status ?? 'ongoing',
        }))
        form.clearErrors()
    },
)
</script>

<template>
    <DialogForm
        :open="open"
        title="Edit Supply"
        :description="`Editing ${supply?.vegetable?.name ?? 'supply'} — ${supply?.scheduled_date}`"
        :form="form"
        submit-label="Save Changes"
        max-width="2xl"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <PackageCheck class="size-5 text-primary" />
        </template>

        <div class="space-y-6">
            <!-- ── Schedule ──────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label
                        for="scheduled_date"
                        class="flex items-center gap-1.5"
                    >
                        Scheduled Date
                        <Badge variant="secondary" class="text-xs font-normal">
                            Required
                        </Badge>
                    </Label>
                    <Input
                        id="scheduled_date"
                        v-model="form.scheduled_date"
                        type="date"
                        :class="{
                            'border-destructive': form.errors.scheduled_date,
                        }"
                    />
                    <p
                        v-if="form.errors.scheduled_date"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.scheduled_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="time_slot" class="flex items-center gap-1.5">
                        Time Slot
                        <Badge variant="secondary" class="text-xs font-normal">
                            Required
                        </Badge>
                    </Label>
                    <Select v-model="form.time_slot">
                        <SelectTrigger
                            id="time_slot"
                            :class="{
                                'border-destructive': form.errors.time_slot,
                            }"
                        >
                            <SelectValue placeholder="Select time slot..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="morning">
                                Morning (6 AM – 12 PM)
                            </SelectItem>
                            <SelectItem value="afternoon">
                                Afternoon (12 PM – 6 PM)
                            </SelectItem>
                            <SelectItem value="evening">
                                Evening (6 PM – 10 PM)
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p
                        v-if="form.errors.time_slot"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.time_slot }}
                    </p>
                </div>
            </div>

            <!-- ── Supply Items ──────────────────────────────────────────── -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <Label class="flex items-center gap-1.5">
                        Supply Items
                        <Badge variant="secondary" class="text-xs font-normal">
                            {{ form.items.length }}
                        </Badge>
                    </Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-7 gap-1.5 text-xs"
                        @click="addItem"
                    >
                        <Plus class="size-3" />
                        Add Item
                    </Button>
                </div>

                <p v-if="form.errors.items" class="text-xs text-destructive">
                    {{ form.errors.items }}
                </p>

                <div
                    v-if="form.items.length === 0"
                    class="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground"
                >
                    No items yet. Add at least one supply item.
                </div>

                <div
                    v-for="(item, index) in form.items"
                    :key="item._key"
                    class="flex items-start gap-2"
                >
                    <!-- Variety select -->
                    <div class="flex-1 space-y-1">
                        <Select
                            v-model="item.variety_id"
                            :disabled="isFulfilled(item)"
                        >
                            <SelectTrigger
                                :class="{
                                    'border-destructive':
                                        form.errors[
                                            `items.${index}.variety_id`
                                        ],
                                }"
                            >
                                <SelectValue placeholder="Select variety..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="v in relevantVarieties"
                                    :key="v.id"
                                    :value="String(v.id)"
                                >
                                    {{ v.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="form.errors[`items.${index}.variety_id`]"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors[`items.${index}.variety_id`] }}
                        </p>
                    </div>

                    <!-- Quantity input -->
                    <div class="w-28 space-y-1">
                        <Input
                            v-model.number="item.quantity_kg"
                            type="number"
                            step="0.1"
                            min="0.1"
                            placeholder="kg"
                            :disabled="isFulfilled(item)"
                            :class="{
                                'border-destructive':
                                    form.errors[`items.${index}.quantity_kg`],
                            }"
                        />
                        <p
                            v-if="form.errors[`items.${index}.quantity_kg`]"
                            class="text-xs text-destructive"
                        >
                            {{ form.errors[`items.${index}.quantity_kg`] }}
                        </p>
                    </div>

                    <!-- Status badge (fulfilled) or remove button -->
                    <div class="flex h-9 items-center">
                        <Badge
                            v-if="isFulfilled(item)"
                            variant="secondary"
                            class="text-xs"
                        >
                            Fulfilled
                        </Badge>
                        <Button
                            v-else
                            type="button"
                            variant="ghost"
                            size="icon"
                            class="size-9 shrink-0 text-muted-foreground hover:text-destructive"
                            @click="removeItem(index)"
                        >
                            <Trash2 class="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    </DialogForm>
</template>
