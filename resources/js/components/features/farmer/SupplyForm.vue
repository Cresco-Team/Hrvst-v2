<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { PackageCheck, Plus, Trash2 } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Farmer/SupplyController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
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
import type {
    FarmerSupplyDataFixed,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '@/types'

interface Props {
    open: boolean
    supply?: FarmerSupplyDataFixed | null
    vegetableOptions?: VegetableOptionsByCategory
    varietyOptions?: VarietyOptionsByVegetable
}

const props = withDefaults(defineProps<Props>(), { supply: null })
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const isEditMode = computed(() => !!props.supply)

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

const form = useForm({
    vegetable_id: '',
    scheduled_date: '',
    time_slot: '',
    items: [] as Array<{
        _key: number
        id: number | null
        variety_id: string
        quantity_kg: string
        status: string
    }>,
})

// In create mode, resolve the vegetable name from vegetableOptions so we can
// filter the varietyOptions lookup (which is keyed by vegetable name, not id).
const selectedVegetableName = computed(() => {
    if (isEditMode.value) return props.supply?.vegetable?.name ?? null
    if (!form.vegetable_id || !props.vegetableOptions) return null
    for (const veggies of Object.values(props.vegetableOptions)) {
        const found = veggies.find((v) => String(v.id) === form.vegetable_id)
        if (found) return found.name
    }
    return null
})

const relevantVarieties = computed(() => {
    const name = selectedVegetableName.value
    if (!name || !props.varietyOptions) return []
    return props.varietyOptions[name] ?? []
})

function toInputDate(dateStr: string | null | undefined): string {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10)
}

function isFulfilled(item: (typeof form.items)[number]): boolean {
    return item.status === 'fulfilled'
}

function blankItem() {
    return {
        _key: nextKey(),
        id: null,
        variety_id: '',
        quantity_kg: '',
        status: 'ongoing',
    }
}

function addItem(): void {
    form.items.push(blankItem())
}

function removeItem(index: number): void {
    form.items.splice(index, 1)
}

function handleSubmit(): void {
    const options = {
        preserveScroll: true,
        only: ['supplies', 'summary'],
        onSuccess: () => {
            emit('update:open', false)
            form.reset()
            form.items = []
        },
    }

    if (isEditMode.value) {
        form.put(update(props.supply!.id).url, options)
    } else {
        form.post(store().url, options)
    }
}

const minDate = computed(() => {
    const d = new Date()
    d.setDate(d.getDate() + 1)
    return d.toISOString().split('T')[0]
})

watch(
    () => [props.open, props.supply] as const,
    ([isOpen, s]) => {
        if (!isOpen) return

        form.vegetable_id = s ? String(s.vegetable?.id ?? '') : ''
        form.scheduled_date = s ? toInputDate(s.scheduled_date) : ''
        form.time_slot = s?.time_slot ?? ''
        form.items = s
            ? (s.post_items ?? []).map((item) => ({
                  _key: nextKey(),
                  id: item.id,
                  variety_id: String((item as any).variety_id ?? ''),
                  quantity_kg: String(item.quantity_kg ?? ''),
                  status: (item as any).status ?? 'ongoing',
              }))
            : [blankItem()]
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
                ? `Editing ${supply?.vegetable?.name ?? 'supply'} — ${supply?.scheduled_date}`
                : 'Post a new supply with schedule and varieties.'
        "
        :form="form"
        :submit-label="isEditMode ? 'Save Changes' : 'Post Supply'"
        max-width="2xl"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <PackageCheck class="size-5 text-primary" />
        </template>

        <div class="space-y-6">
            <!-- ── Vegetable (create mode only) ─────────────────────── -->
            <div v-if="!isEditMode" class="space-y-2">
                <Label for="vegetable_id" class="flex items-center gap-1.5">
                    Vegetable
                    <Badge variant="secondary" class="text-xs font-normal"
                        >Required</Badge
                    >
                </Label>
                <Select v-model="form.vegetable_id">
                    <SelectTrigger
                        id="vegetable_id"
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
            </div>

            <!-- ── Schedule ──────────────────────────────────────────── -->
            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label
                        for="scheduled_date"
                        class="flex items-center gap-1.5"
                    >
                        Scheduled Date
                        <Badge variant="secondary" class="text-xs font-normal"
                            >Required</Badge
                        >
                    </Label>
                    <Input
                        id="scheduled_date"
                        v-model="form.scheduled_date"
                        type="date"
                        :min="minDate"
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
                        <Badge variant="secondary" class="text-xs font-normal"
                            >Required</Badge
                        >
                    </Label>
                    <Select v-model="form.time_slot">
                        <SelectTrigger
                            id="time_slot"
                            :class="{
                                'border-destructive': form.errors.time_slot,
                            }"
                        >
                            <SelectValue placeholder="Select time..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="morning"
                                >Morning (6 AM – 12 PM)</SelectItem
                            >
                            <SelectItem value="afternoon"
                                >Afternoon (12 PM – 6 PM)</SelectItem
                            >
                            <SelectItem value="evening"
                                >Evening (6 PM – 10 PM)</SelectItem
                            >
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

            <!-- ── Supply Items ──────────────────────────────────────── -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <Label class="flex items-center gap-1.5">
                        Supply Varieties
                        <Badge variant="secondary" class="text-xs font-normal">
                            {{ form.items.length }}
                        </Badge>
                    </Label>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        class="h-7 gap-1.5 text-xs"
                        :disabled="!selectedVegetableName"
                        @click="addItem"
                    >
                        <Plus class="size-3" />
                        Add Variety
                    </Button>
                </div>

                <p
                    v-if="!isEditMode && !selectedVegetableName"
                    class="text-xs text-muted-foreground italic"
                >
                    Select a vegetable above to add supply items.
                </p>

                <p v-if="form.errors.items" class="text-xs text-destructive">
                    {{ form.errors.items }}
                </p>

                <div
                    v-if="form.items.length === 0 && selectedVegetableName"
                    class="rounded-md border border-dashed p-4 text-center text-sm text-muted-foreground"
                >
                    No items yet. Add at least one variety.
                </div>

                <div
                    v-for="(item, index) in form.items"
                    :key="item._key"
                    class="flex items-start gap-2"
                >
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
                                    <span
                                        v-if="v.current_price"
                                        class="ml-1 text-xs text-muted-foreground"
                                    >
                                        ₱{{ v.current_price.min }}–{{
                                            v.current_price.max
                                        }}/kg
                                    </span>
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
                            class="size-9 text-muted-foreground hover:text-destructive"
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
