<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import {
	Dialog,
	DialogContent,
	DialogFooter,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import {
	Select,
	SelectContent,
	SelectItem,
	SelectTrigger,
	SelectValue,
} from '@/components/ui/select'
import type { DealerPostItemResource, VarietyOptionsByVegetable } from '@/types'

const props = defineProps<{
	open: boolean
	item: DealerPostItemResource | null
	varietyOptions: VarietyOptionsByVegetable | undefined
	updateUrl: string
}>()

const emit = defineEmits<{ 'update:open': [value: boolean] }>()

const form = useForm({
	variety_id: '',
	quantity_kg: '',
	unit_price: '',
})

watch(
	() => props.item,
	(item) => {
		if (!item) return
		form.variety_id = String(item.variety_id)
		form.quantity_kg = String(item.quantity_kg)
		form.unit_price = item.unit_price != null ? String(item.unit_price) : ''
	},
	{ immediate: true },
)

// Varieties available for this item's vegetable — varietyOptions keyed by vegetable_id
const varietiesForItem = computed(() => {
	if (!props.item || !props.varietyOptions) return []
	return props.varietyOptions[props.item.vegetable_id] ?? []
})

function submit() {
	form.put(props.updateUrl, {
		preserveScroll: true,
		onSuccess: () => emit('update:open', false),
	})
}
</script>

<template>
	<Dialog :open="open" @update:open="emit('update:open', $event)">
		<DialogContent class="sm:max-w-md">
			<DialogHeader>
				<DialogTitle>Edit Item — {{ item?.vegetable_name }}</DialogTitle>
			</DialogHeader>

			<div class="grid gap-4 py-2">
				<div class="grid gap-1.5">
					<Label>Variety</Label>
					<Select v-model="form.variety_id">
						<SelectTrigger>
							<SelectValue placeholder="Select variety" />
						</SelectTrigger>
						<SelectContent>
							<SelectItem
								v-for="v in varietiesForItem"
								:key="v.id"
								:value="String(v.id)"
							>
								{{ v.name }}
							</SelectItem>
						</SelectContent>
					</Select>
					<p v-if="form.errors.variety_id" class="text-xs text-destructive">
						{{ form.errors.variety_id }}
					</p>
				</div>

				<div class="grid gap-1.5">
					<Label>Quantity (kg)</Label>
					<Input
						v-model="form.quantity_kg"
						type="number"
						min="0.1"
						step="0.1"
					/>
					<p v-if="form.errors.quantity_kg" class="text-xs text-destructive">
						{{ form.errors.quantity_kg }}
					</p>
				</div>

				<div class="grid gap-1.5">
					<Label>Unit Price (₱/kg) <span class="text-muted-foreground text-xs">optional</span></Label>
					<Input
						v-model="form.unit_price"
						type="number"
						min="0"
						step="0.01"
						placeholder="0.00"
					/>
					<p v-if="form.errors.unit_price" class="text-xs text-destructive">
						{{ form.errors.unit_price }}
					</p>
				</div>
			</div>

			<DialogFooter>
				<Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
				<Button :disabled="form.processing" @click="submit">
					{{ form.processing ? 'Saving…' : 'Save Changes' }}
				</Button>
			</DialogFooter>
		</DialogContent>
	</Dialog>
</template>
