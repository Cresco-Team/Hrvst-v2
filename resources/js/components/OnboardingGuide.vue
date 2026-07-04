<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3'
import {
	CalendarClock,
	CircleCheck,
	PackageCheck,
	ShoppingBag,
	Sprout,
} from 'lucide-vue-next'
import { computed, onMounted, ref, type Component } from 'vue'
import {
	Dialog,
	DialogContent,
	DialogDescription,
	DialogHeader,
	DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { useOnboardingGuide } from '@/composables/useOnboardingGuide'
import { complete } from '@/actions/App/Http/Controllers/OnboardingController'

interface OnboardingStep {
	icon: Component
	title: string
	body: string
}

const FARMER_STEPS: OnboardingStep[] = [
	{
		icon: Sprout,
		title: 'Post a Supply',
		body: 'Tell dealers what vegetables you have ready, how much, and your preferred pickup day and time.',
	},
	{
		icon: CalendarClock,
		title: 'Wait for Pickup Day',
		body: 'Dealers browsing supplies can see your posting. You don\'t need to do anything until your scheduled date.',
	},
	{
		icon: PackageCheck,
		title: 'Mark it Fulfilled',
		body: 'Once a dealer picks up your delivery, mark the item as fulfilled from your dashboard.',
	},
]

const DEALER_STEPS: OnboardingStep[] = [
	{
		icon: ShoppingBag,
		title: 'Post a Request',
		body: 'Tell farmers what vegetables you need, how much, and your preferred pickup day and time.',
	},
	{
		icon: CalendarClock,
		title: 'Wait for a Match',
		body: 'Farmers browsing requests can see your posting. You don\'t need to do anything until your scheduled date.',
	},
	{
		icon: PackageCheck,
		title: 'Mark it Fulfilled',
		body: 'Once you\'ve picked up from a farmer, mark the item as fulfilled from your dashboard.',
	},
]

const page = usePage()
const { isOpen, open, close } = useOnboardingGuide()

const wasForced = page.props.needsOnboarding === true

const role = computed<'farmer' | 'dealer' | null>(() => {
	const roles = page.props.auth.user?.roles ?? []
	if (roles.includes('farmer')) return 'farmer'
	if (roles.includes('dealer')) return 'dealer'
	return null
})

const steps = computed<OnboardingStep[]>(() =>
	role.value === 'dealer' ? DEALER_STEPS : FARMER_STEPS,
)

const currentStep = ref(0)
const isLastStep = computed(() => currentStep.value === steps.value.length - 1)

function next(): void {
	if (isLastStep.value) {
		finish()
		return
	}
	currentStep.value++
}

function finish(): void {
	currentStep.value = 0
	close()

	if (wasForced) {
		router.post(complete().url, {}, { preserveScroll: true })
	}
}

function handleOpenChange(value: boolean): void {
	if (!value && wasForced) return
	if (!value) {
		finish()
		return
	}
	isOpen.value = value
}

onMounted(() => {
	if (wasForced) open()
})
</script>

<template>
	<Dialog :open="isOpen" @update:open="handleOpenChange">
		<DialogContent
			class="sm:max-w-md"
			@pointer-down-outside="wasForced && $event.preventDefault()"
			@escape-key-down="wasForced && $event.preventDefault()"
		>
			<DialogHeader class="items-center text-center">
				<div
					class="mb-2 flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary"
				>
					<component :is="steps[currentStep].icon" class="size-7" />
				</div>
				<DialogTitle>{{ steps[currentStep].title }}</DialogTitle>
				<DialogDescription>{{ steps[currentStep].body }}</DialogDescription>
			</DialogHeader>

			<div class="flex items-center justify-center gap-1.5 py-2">
				<span
					v-for="(_, index) in steps"
					:key="index"
					class="size-1.5 rounded-full transition-colors"
					:class="index === currentStep ? 'bg-primary' : 'bg-muted'"
				/>
			</div>

			<div class="flex justify-end gap-2">
				<Button
					v-if="!wasForced || currentStep > 0"
					variant="ghost"
					size="sm"
					@click="currentStep > 0 ? currentStep-- : finish()"
				>
					{{ currentStep > 0 ? 'Back' : 'Close' }}
				</Button>
				<Button size="sm" @click="next">
					<CircleCheck v-if="isLastStep" class="size-4" />
					{{ isLastStep ? "Let's Go" : 'Next' }}
				</Button>
			</div>
		</DialogContent>
	</Dialog>
</template>
