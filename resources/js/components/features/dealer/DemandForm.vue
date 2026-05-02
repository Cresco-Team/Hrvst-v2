<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Plus, ShoppingBag, Trash2 } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { store, update } from '@/actions/App/Http/Controllers/Dealer/DemandController'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
	Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue,
} from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import type {
	DealerDemandResource, PostTimeSlot, VegetableOptionsByCategory, VarietyOptionsByVegetable,
} from '@/types'

interface DemandItem {
	variety_id: string
	quantity_kg: string
	unit_price: string
	time_slot: PostTimeSlot | ''
}

interface Props {
	open: boolean
	demand?: DealerDemandResource | null
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

function blankItem(): DemandItem {
	return { variety_id: '', quantity_kg: '', unit_price: '', time_slot: 'morning' }
}

const form = useForm<{
	vegetable_id: string
	scheduled_at: string
	items: DemandItem[]
}>({
	vegetable_id: '',
	scheduled_at: '',
	items: [blankItem()],
})

const isEditMode = computed(() => !!props.demand)

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

const allVarieties = computed(() => {
	if (!props.varietyOptions) return []
	return Object.values(props.varietyOptions).flat()
})

function priceHintFor(varietyId: string) {
	return allVarieties.value.find((v) => String(v.id) === varietyId)?.current_price ?? null
}

function addItem() {
	form.items.push(blankItem())
}

function removeItem(index: number) {
	form.items.splice(index, 1)
}

function handleSubmit() {
	const routeData = props.demand ? update(props.demand.id) : store()

	form
		.transform((data) => {
			if (props.demand) return { ...data, _method: 'PUT' }
			return data
		})
		.post(routeData.url, {
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
		const d = props.demand
		form.vegetable_id = String(d?.vegetable?.id ?? '')
		form.scheduled_at = d?.scheduled_at ?? ''
		form.items = d?.items?.length
			? d.items.map((i) => ({
				variety_id: String(i.variety_id),
				quantity_kg: String(i.quantity_kg),
				unit_price: String(i.unit_price ?? ''),
				time_slot: (i.time_slot ?? 'morning') as PostTimeSlot | '',
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
		max-width="2xl"
		@update:open="emit('update:open', $event)"
		@submit="handleSubmit"
	>
		<template #icon>
			<ShoppingBag class="size-5 text-primary" />
		</template>

		<div class="space-y-6">

			<!-- Vegetable -->
			<div class="space-y-2">
				<Label for="vegetable" class="flex items-center gap-1.5">
					Vegetable
					<Badge variant="secondary" class="text-xs font-normal">Required</Badge>
				</Label>
				<Select v-model="form.vegetable_id" :disabled="isEditMode">
					<SelectTrigger id="vegetable" :class="{ 'border-destructive': form.errors.vegetable_id }">
						<SelectValue placeholder="Select a vegetable..." />
					</SelectTrigger>
					<SelectContent>
						<SelectGroup v-for="(vegetables, category) in vegetableOptions" :key="category">
							<SelectLabel>{{ category }}</SelectLabel>
							<SelectItem v-for="v in vegetables" :key="v.id" :value="String(v.id)">
								{{ v.name }}
							</SelectItem>
						</SelectGroup>
					</SelectContent>
				</Select>
				<p v-if="form.errors.vegetable_id" class="text-xs text-destructive">{{ form.errors.vegetable_id }}</p>
				<p v-else-if="isEditMode" class="text-xs text-muted-foreground">Vegetable cannot be changed after creation</p>
			</div>

			<!-- Transaction Date -->
			<div class="space-y-2">
				<Label for="scheduled_at" class="flex items-center gap-1.5">
					Transaction Date
					<Badge variant="secondary" class="text-xs font-normal">Required</Badge>
				</Label>
				<Input
					id="scheduled_at"
					v-model="form.scheduled_at"
					type="date"
					:min="minDate"
					:max="maxDate"
					:class="{ 'border-destructive': form.errors.scheduled_at }"
				/>
				<p v-if="form.errors.scheduled_at" class="text-xs text-destructive">{{ form.errors.scheduled_at }}</p>
				<p v-else class="text-xs text-muted-foreground">Post will auto-archive after this date (max 3 months)</p>
			</div>

			<Separator />

			<!-- Variety items -->
			<div class="space-y-4">
				<div class="flex items-center justify-between">
					<Label class="text-sm font-medium">Varieties Needed</Label>
					<Button type="button" variant="outline" size="sm" class="gap-1.5" @click="addItem">
						<Plus class="size-3.5" />
						Add Variety
					</Button>
				</div>

				<p v-if="(form.errors as Record<string, string>).items" class="text-xs text-destructive">
					{{ (form.errors as Record<string, string>).items }}
				</p>

				<div
					v-for="(item, index) in form.items"
					:key="index"
					class="relative rounded-lg border bg-muted/30 p-4 space-y-4"
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

					<!-- Variety -->
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
								<template v-for="(varieties, vegetableName) in varietyOptions" :key="vegetableName">
									<SelectItem v-for="v in varieties" :key="v.id" :value="String(v.id)">
										{{ vegetableName }} — {{ v.name }}
									</SelectItem>
								</template>
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
							<Label :for="`price-${index}`" class="text-xs">Offered Price (₱/kg)</Label>
							<Input
								:id="`price-${index}`"
								v-model.number="item.unit_price"
								type="number"
								step="0.01"
								min="0"
								placeholder="Optional"
								:class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.unit_price`] }"
							/>
							<p v-if="(form.errors as Record<string, string>)[`items.${index}.unit_price`]" class="text-xs text-destructive">
								{{ (form.errors as Record<string, string>)[`items.${index}.unit_price`] }}
							</p>
						</div>
					</div>

					<div class="space-y-1.5">
						<Label :for="`slot-${index}`" class="text-xs">Preferred Time Slot</Label>
						<Select v-model="item.time_slot">
							<SelectTrigger
								:id="`slot-${index}`"
								:class="{ 'border-destructive': (form.errors as Record<string, string>)[`items.${index}.time_slot`] }"
							>
								<SelectValue placeholder="Select time slot..." />
							</SelectTrigger>
							<SelectContent>
								<SelectItem v-for="opt in TIME_SLOT_OPTIONS" :key="opt.value" :value="opt.value">
									{{ opt.label }}
								</SelectItem>
							</SelectContent>
						</Select>
						<p v-if="(form.errors as Record<string, string>)[`items.${index}.time_slot`]" class="text-xs text-destructive">
							{{ (form.errors as Record<string, string>)[`items.${index}.time_slot`] }}
						</p>
					</div>

				</div>
			</div>

		</div>
	</DialogForm>
</template>
