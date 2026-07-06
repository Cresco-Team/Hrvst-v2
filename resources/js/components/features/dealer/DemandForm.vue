<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { CalendarIcon, Plus, Trash2 } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { CalendarDate, today, getLocalTimeZone, DateFormatter } from '@internationalized/date'
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/Dealer/DemandController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar } from '@/components/ui/calendar'
import { Label } from '@/components/ui/label'
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/components/ui/number-field'
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
import {
    Table,
    TableBody,
    TableCell,
    TableEmpty,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import {
    useVegetableAvailability,
    netKgClass,
    formatNetKg,
} from '@/composables/useVegetableAvailability'
import type {
    DealerDemandDataFixed,
    PostTimeSlot,
    VarietyOptionsByVegetable,
    VegetableOptionsByCategory,
} from '@/types'

interface DemandItem {
    _key: number
    vegetable_id: string
    quantity_kg: number | null
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

let _keyCounter = 0
const nextKey = (): number => ++_keyCounter

function blankItem(): DemandItem {
    return { _key: nextKey(), vegetable_id: '', quantity_kg: null }
}

const form = useForm<{
    scheduled_date: string
    time_slot: PostTimeSlot | ''
    items: DemandItem[]
}>({
    scheduled_date: '',
    time_slot: '',
    items: [blankItem()],
})

const isEditMode = computed(() => !!props.demand)

const { getState, getData } = useVegetableAvailability(
    () => form.scheduled_date,
    () => form.time_slot,
    () => form.items.map((i) => i.vegetable_id),
)

const df = new DateFormatter('en-US', { dateStyle: 'long' })
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

function toInputDate(dateStr: string | null | undefined): string {
    if (!dateStr) return ''
    const d = new Date(dateStr)
    return isNaN(d.getTime()) ? '' : d.toISOString().slice(0, 10)
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
    () => [props.open, props.demand] as const,
    ([isOpen, d]) => {
        if (!isOpen) return
        form.scheduled_date = d ? toInputDate(d.scheduled_date) : ''
        form.time_slot = (d?.time_slot ?? '') as PostTimeSlot | ''
        form.items = d?.post_items?.length
            ? d.post_items.map((i) => ({
                  _key: nextKey(),
                  vegetable_id: String(i.vegetable_id ?? ''),
                  quantity_kg: i.quantity_kg ?? null,
              }))
            : [blankItem()]
        form.clearErrors()
    },
)
</script>

<template>
    <DialogForm
        :open="open"
        :title="isEditMode ? `Edit ${demand?.scheduled_date} supplies` : 'New Request Schedule'"
        :form="form"
        :submit-label="isEditMode ? 'Save Changes' : 'Create Schedule'"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <div class="space-y-6">
            <div class="flex justify-between gap-4">
                <div class="space-y-2">
                    <Label for="scheduled_date" class="flex items-center gap-1.5">
                        Transaction Day
                        <Badge variant="destructive" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Popover v-slot="{ close }">
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
                                {{ form.scheduled_date ? df.format(calendarDate!.toDate(getLocalTimeZone())) : 'Pick a date' }}
                            </Button>
                        </PopoverTrigger>
                        <PopoverContent class="w-auto p-0" align="start">
                            <Calendar
                                v-model="calendarDate"
                                layout="month-only"
                                :min-value="minDateValue"
                                :max-value="maxDateValue"
                                initial-focus
                                @update:model-value="close"
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
                        <Badge variant="destructive" class="text-xs font-normal">Required</Badge>
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

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Varieties Needed <span class="text-destructive">*</span></TableHead>
                        <TableHead class="text-center">Kilogram <span class="text-destructive">*</span></TableHead>
                        <TableHead class="text-end">
                            <Button type="button" variant="outline" size="sm" class="h-7 gap-1.5 text-xs" @click="addItem">
                                <Plus class="size-3" />
                            </Button>
                        </TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableEmpty v-if="form.items.length === 0" :colspan="3">
                        <span :class="form.errors.items ? `text-destructive` : ''">
                            No requests yet. Add at least one request.
                        </span>
                    </TableEmpty>

                    <TableRow v-for="(item, index) in form.items" :key="item._key">
                        <TableCell class="relative pb-6">
                            <Select v-model="item.vegetable_id">
                                <SelectTrigger :class="{ 'border-destructive': form.errors[`items.${index}.vegetable_id`] }">
                                    <SelectValue placeholder="Select variety..." />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectGroup v-for="(varieties, vegetableName) in varietyOptions" :key="vegetableName">
                                        <SelectLabel>{{ vegetableName }}</SelectLabel>
                                        <SelectItem v-for="v in varieties" :key="v.id" :value="String(v.id)">
                                            {{ vegetableName }}: {{ v.name }}
                                        </SelectItem>
                                    </SelectGroup>
                                </SelectContent>
                            </Select>

                            <div v-if="item.vegetable_id && form.scheduled_date" class="mt-1.5 flex items-center gap-1">
                                <Skeleton v-if="getState(item.vegetable_id).status === 'loading'" class="h-3.5 w-20 rounded" />
                                <template v-else-if="getData(item.vegetable_id)">
                                    <span :class="netKgClass(getData(item.vegetable_id)!.net_kg)" class="text-xs font-medium tabular-nums">
                                        {{ formatNetKg(getData(item.vegetable_id)!.net_kg) }}
                                    </span>
                                </template>
                            </div>

                            <p v-if="form.errors[`items.${index}.vegetable_id`]" class="absolute bottom-1 text-xs text-destructive">
                                {{ form.errors[`items.${index}.vegetable_id`] }}
                            </p>
                        </TableCell>

                        <TableCell class="relative max-w-30 space-y-1 pb-5">
                            <NumberField
                                v-model="item.quantity_kg"
                                :min="0.00"
                                :max="99999.99"
                                :step="0.1"
                                :format-options="{ style: 'unit', unit: 'kilogram', unitDisplay: 'short', minimumFractionDigits: 0, maximumFractionDigits: 1 }"
                            >
                                <NumberFieldContent>
                                    <NumberFieldDecrement />
                                    <NumberFieldInput :class="{ 'border-destructive': form.errors[`items.${index}.quantity_kg`] }" />
                                    <NumberFieldIncrement />
                                </NumberFieldContent>
                            </NumberField>
                            <p v-if="form.errors[`items.${index}.quantity_kg`]" class="absolute text-xs text-destructive">
                                {{ form.errors[`items.${index}.quantity_kg`] }}
                            </p>
                        </TableCell>

                        <TableCell class="text-end">
                            <Button type="button" variant="ghost" size="icon" class="size-9 text-muted-foreground hover:text-destructive" @click="removeItem(index)">
                                <Trash2 class="size-4" />
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </DialogForm>
</template>
