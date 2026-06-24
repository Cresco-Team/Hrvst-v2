<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { CalendarIcon, Plus, ShoppingBag, Trash2 } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { CalendarDate, today, getLocalTimeZone } from '@internationalized/date'
import { store, update } from '@/actions/App/Http/Controllers/Dealer/DemandController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover'
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
    DealerDemandDataFixed,
    PostTimeSlot,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '@/types'

interface DemandItem {
    _key: number
    variety_id: string
    quantity_kg: string
}

interface Props {
    open: boolean
    demand?: DealerDemandDataFixed | null
    vegetableOptions?: VegetableOptionsByCategory
    varietyOptions?: VarietyOptionsByVegetable
}

const props = withDefaults(defineProps<Props>(), { demand: null })
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const TIME_SLOT_OPTIONS: { value: PostTimeSlot; label: string }[] = [
    { value: 'morning', label: 'Morning (6 AM – 12 PM)' },
    { value: 'afternoon', label: 'Afternoon (12 PM – 6 PM)' },
    { value: 'evening', label: 'Evening (6 PM – 10 PM)' },
]

// UI-only filter — not submitted to backend
const filterVegetableId = ref<string>('')

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

function blankItem(): DemandItem {
    return { _key: nextKey(), variety_id: '', quantity_kg: '' }
}

const form = useForm<{
    scheduled_date: string
    time_slot: PostTimeSlot | ''
    items: DemandItem[]
}>({
    scheduled_date: '',
    time_slot: 'morning',
    items: [blankItem()],
})

const isEditMode = computed(() => !!props.demand)

const minDateValue = computed(() => today(getLocalTimeZone()).add({ days: 1 }))
const maxDateValue = computed(() => today(getLocalTimeZone()).add({ months: 3 }))

const calendarDate = computed({
    get(): CalendarDate | undefined {
        if (!form.scheduled_date) return undefined
        const [y, m, d] = form.scheduled_date.split('-').map(Number)
        return new CalendarDate(y, m, d)
    },
    set(val: CalendarDate | undefined): void {
        form.scheduled_date = val ? val.toString() : ''
    },
})

function addItem(): void {
    form.items.push(blankItem())
}

function removeItem(index: number): void {
    form.items.splice(index, 1)
}

function handleSubmit(): void {
    const options = {
        preserveScroll: true,
        only: ['demands', 'summary'],
        onSuccess: () => {
            emit('update:open', false)
            form.reset()
            form.items = [blankItem()]
        },
    }

    if (isEditMode.value) {
        form.put(update(props.demand!.id).url, options)
    } else {
        form.post(store().url, options)
    }
}

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return
        const d = props.demand
        filterVegetableId.value = ''
        form.scheduled_date = d?.scheduled_date ?? ''
        form.time_slot = (d?.time_slot ?? 'morning') as PostTimeSlot | ''
        form.items = d?.post_items?.length
            ? d.post_items.map((i) => ({
                  _key: nextKey(),
                  variety_id: String((i as any).variety_id ?? ''),
                  quantity_kg: String(i.quantity_kg ?? ''),
              }))
            : [blankItem()]
        form.clearErrors()
    },
)
</script>

<template>
    <DialogForm
        :open="open"
        :title="isEditMode ? 'Edit Demand' : 'New Demand'"
        :description="isEditMode ? 'Update your demand details.' : 'Post a purchase request for farmers.'"
        :form="form"
        :submit-label="isEditMode ? 'Update Demand' : 'Post Demand'"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <ShoppingBag class="size-5 text-primary" />
        </template>

        <div class="space-y-6">

            <!-- ── Schedule ──────────────────────────────────────────── -->
            <div class="flex justify-between gap-4">
                <div class="space-y-2">
                    <Label for="scheduled_date" class="flex items-center gap-1.5">
                        Transaction Date
                        <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Popover>
                        <PopoverTrigger as-child>
                            <Button
                                id="scheduled_date"
                                variant="outline"
                                :class="[
                                    'w-full justify-start text-left font-normal',
                                    !form.scheduled_date && 'text-muted-foreground',
                                    form.errors.scheduled_date && 'border-destructive text-destructive',
                                ]"
                            >
                                <CalendarIcon class="mr-2 h-4 w-4" />
                                {{
                                    form.scheduled_date
                                        ? new Date(form.scheduled_date + 'T00:00:00').toLocaleDateString()
                                        : 'Pick a date'
                                }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar
                                v-model="calendarDate"
                                :min-value="minDateValue"
                                :max-value="maxDateValue"
                                initial-focus
                            />
                        </PopoverContent>
                    </Popover>
                    <p v-if="form.errors.scheduled_date" class="text-xs text-destructive">
                        {{ form.errors.scheduled_date }}
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="time_slot" class="flex items-center gap-1.5">
                        Preferred Time Slot
                        <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Select v-model="form.time_slot">
                        <SelectTrigger id="time_slot" :class="{ 'border-destructive': form.errors.time_slot }">
                            <SelectValue placeholder="Select a time slot..." />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="opt in TIME_SLOT_OPTIONS" :key="opt.value" :value="opt.value">
                                {{ opt.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.time_slot" class="text-xs text-destructive">
                        {{ form.errors.time_slot }}
                    </p>
                </div>
            </div>

            <!-- ── Variety Items ──────────────────────────────────────── -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <Label class="flex items-center gap-1.5">
                        Varieties Needed
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
                        Add Variety
                    </Button>
                </div>
                
                <p v-if="(form.errors as Record<string, string>).items" class="text-xs text-destructive">
                    {{ (form.errors as Record<string, string>).items }}
                </p>

                <div
                    v-if="form.items.length === 0"
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
                        <Select v-model="item.variety_id">
                            <SelectTrigger
                                :class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.variety_id`] }"
                            >
                                <SelectValue placeholder="Select variety..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectGroup
                                    v-for="(varieties, vegetableName) in varietyOptions"
                                    :key="vegetableName"
                                >
                                    <SelectLabel>{{ vegetableName }}</SelectLabel>
                                    <SelectItem
                                        v-for="v in varieties"
                                        :key="v.id"
                                        :value="String(v.id)"
                                    >
                                        {{ vegetableName }}: {{ v.name }}
                                    </SelectItem>
                                </SelectGroup>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="(form.errors as Record<string, string>)[`items.${index}.variety_id`]"
                            class="text-xs text-destructive"
                        >
                            {{ (form.errors as Record<string, string>)[`items.${index}.variety_id`] }}
                        </p>
                    </div>

                    <div class="w-28 space-y-1">
                        <Input
                            v-model.number="item.quantity_kg"
                            type="number"
                            step="0.1"
                            min="0.1"
                            placeholder="kg"
                            :class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.quantity_kg`] }"
                        />
                        <p
                            v-if="(form.errors as Record<string, string>)[`items.${index}.quantity_kg`]"
                            class="text-xs text-destructive"
                        >
                            {{ (form.errors as Record<string, string>)[`items.${index}.quantity_kg`] }}
                        </p>
                    </div>

                    <div class="flex h-9 items-center">
                        <Button
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
