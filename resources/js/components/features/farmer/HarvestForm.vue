<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Leaf, Plus, Trash2 } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { harvest } from '@/actions/App/Http/Controllers/Farmer/SupplyController'
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
import { Separator } from '@/components/ui/separator'
import type { FarmerSupplyResource, PostTimeSlot, VarietyOptionsByVegetable } from '@/types'

interface HarvestItem {
	variety_id: string
	quantity_kg: string
	unit_price: string
}

interface Props {
	open: boolean
	supply: FarmerSupplyResource | null
	varietyOptions?: VarietyOptionsByVegetable
}

const props = defineProps<Props>()
const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const TIME_SLOT_OPTIONS: { value: PostTimeSlot; label: string }[] = [
	{ value: 'morning', label: 'Morning (6 AM – 12 PM)' },
	{ value: 'afternoon', label: 'Afternoon (12 PM – 6 PM)' },
	{ value: 'evening', label: 'Evening (6 PM – 10 PM)' },
]

// Only show varieties that belong to the supply post's vegetable.
// varietyOptions is keyed by vegetable name — match against the post's vegetable.
const availableVarieties = computed(() => {
	const vegetableName = props.supply?.vegetable?.name
	if (!vegetableName || !props.varietyOptions) return []
	return props.varietyOptions[vegetableName] ?? []
})

function blankItem(): HarvestItem {
	return { variety_id: '', quantity_kg: '', unit_price: '' }
}

const form = useForm<{
	scheduled_date: string
	time_slot: PostTimeSlot | ''
	items: HarvestItem[]
}>({
	scheduled_date: '',
	time_slot: 'morning',
	items: [blankItem()],
})

const minDate = computed(() => {
	const d = new Date()
	d.setDate(d.getDate() + 1)
	return d.toISOString().split('T')[0]
})

const maxDate = computed(() => {
	const d = new Date()
	d.setMonth(d.getMonth() + 3)
	return d.toISOString().split('T')[0]
})

function priceHintFor(varietyId: string) {
	return availableVarieties.value.find((v) => String(v.id) === varietyId)?.current_price ?? null
}

function addItem() {
	form.items.push(blankItem())
}
function removeItem(index: number) {
	form.items.splice(index, 1)
}

function handleSubmit() {
	if (!props.supply) return
	form.post(harvest(props.supply.id).url, {
		preserveScroll: true,
		onSuccess: () => {
			emit('update:open', false)
			form.reset()
			form.items = [blankItem()]
		},
	})
}

watch(
	() => props.open,
	(isOpen) => {
		if (!isOpen) return
		form.scheduled_date = ''
		form.time_slot = 'morning'
		form.items = [blankItem()]
		form.clearErrors()
	},
)
</script>

<template>
	<DialogForm
		:open="open"
		title="Record Harvest"
		:description="`Break down ${supply?.vegetable?.name ?? 'supply'} into varieties and schedule delivery.`"
		:form="form"
		submit-label="Confirm Harvest"
		max-width="2xl"
		@update:open="emit('update:open', $event)"
		@submit="handleSubmit"
	>
		<template #icon>
			<Leaf class="size-5 text-primary" />
		</template>

		<div class="space-y-6">

			<div class="space-y-2">
				<Label for="scheduled_date" class="flex items-center gap-1.5">
					Delivery Date
					<Badge variant="secondary" class="text-xs font-normal">Required</Badge>
				</Label>
				<Input
					id="scheduled_date"
					v-model="form.scheduled_date"
					type="date"
					:min="minDate"
					:max="maxDate"
					:class="{ 'border-destructive': form.errors.scheduled_date }"
				/>
				<p v-if="form.errors.scheduled_date" class="text-xs text-destructive">{{ form.errors.scheduled_date }}</p>
				<p v-else class="text-xs text-muted-foreground">When will you bring this to the trading post?</p>
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
				<p v-if="form.errors.time_slot" class="text-xs text-destructive">{{ form.errors.time_slot }}</p>
				<p v-else class="text-xs text-muted-foreground">When are you available for delivery?</p>
			</div>

			<Separator />

			<div class="space-y-4">
				<div class="flex items-center justify-between">
					<div>
						<Label class="text-sm font-medium">Harvest Items</Label>
						<p class="text-xs text-muted-foreground mt-0.5">
							Varieties of <span class="font-medium text-foreground">{{ supply?.vegetable?.name }}</span>
						</p>
					</div>
					<Button type="button" variant="outline" size="sm" class="gap-1.5" @click="addItem">
						<Plus class="size-3.5" />
						Add Variety
					</Button>
				</div>

				<p v-if="availableVarieties.length === 0" class="text-xs text-muted-foreground italic">
					No varieties registered for {{ supply?.vegetable?.name ?? 'this vegetable' }}.
				</p>

				<p v-if="(form.errors as Record<string, string>).items" class="text-xs text-destructive">
					{{ (form.errors as Record<string, string>).items }}
				</p>

				<div
					v-for="(item, index) in form.items"
					:key="index"
					class="rounded-lg border bg-muted/30 p-4 space-y-4"
				>
					<div class="flex items-center justify-between">
						<span class="text-xs font-semibold text-muted-foreground">Item {{ index + 1 }}</span>
						<Button
							v-if="form.items.length > 1"
							type="button"
							variant="ghost"
							size="icon-sm"
							class="text-destructive hover:text-destructive"
							@click="removeItem(index)"
						>
							<Trash2 class="size-4" />
						</Button>
					</div>

					<div class="space-y-1.5">
						<Label :for="`variety-${index}`" class="text-xs">Variety</Label>
						<Select v-model="item.variety_id">
							<SelectTrigger
								:id="`variety-${index}`"
								:class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.variety_id`] }"
							>
								<SelectValue placeholder="Select variety..." />
							</SelectTrigger>
							<SelectContent>
								<SelectItem
									v-for="v in availableVarieties"
									:key="v.id"
									:value="String(v.id)"
								>
									{{ v.name }}
								</SelectItem>
							</SelectContent>
						</Select>
						<p v-if="(form.errors as Record<string, string>)[`items.${index}.variety_id`]" class="text-xs text-destructive">
							{{ (form.errors as Record<string, string>)[`items.${index}.variety_id`] }}
						</p>

						<div
							v-if="item.variety_id && priceHintFor(item.variety_id)"
							class="flex items-center gap-1.5 rounded-md border border-dashed bg-background px-3 py-1.5 text-xs text-muted-foreground"
						>
							<span>Market price:</span>
							<span class="font-mono font-semibold text-foreground">
								₱{{ priceHintFor(item.variety_id)!.min.toFixed(2) }} –
								₱{{ priceHintFor(item.variety_id)!.max.toFixed(2) }}
							</span>
							<span>/ kg</span>
						</div>
					</div>

					<div class="grid grid-cols-2 gap-3">
						<div class="space-y-1.5">
							<Label :for="`qty-${index}`" class="text-xs">Quantity (kg)</Label>
							<Input
								:id="`qty-${index}`"
								v-model.number="item.quantity_kg"
								type="number"
								step="0.1"
								min="0.1"
								placeholder="0.0"
								:class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.quantity_kg`] }"
							/>
							<p v-if="(form.errors as Record<string, string>)[`items.${index}.quantity_kg`]" class="text-xs text-destructive">
								{{ (form.errors as Record<string, string>)[`items.${index}.quantity_kg`] }}
							</p>
						</div>

						<div class="space-y-1.5">
							<Label :for="`price-${index}`" class="text-xs">Asking Price (₱/kg)</Label>
							<Input
								:id="`price-${index}`"
								v-model.number="item.unit_price"
								type="number"
								step="0.01"
								min="0"
								placeholder="0.00"
								:class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.unit_price`] }"
							/>
							<p v-if="(form.errors as Record<string, string>)[`items.${index}.unit_price`]" class="text-xs text-destructive">
								{{ (form.errors as Record<string, string>)[`items.${index}.unit_price`] }}
							</p>
						</div>
					</div>
				</div>
			</div>

		</div>
	</DialogForm>
</template>
