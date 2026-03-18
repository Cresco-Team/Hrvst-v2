import { computed, ref, watch } from 'vue'

interface UseDialogFormOptions<TData, TFormData> {
	/**
	 * The item being edited (null for create mode)
	 */
	item: () => TData | null

	/**
	 * Whether the dialog is open
	 */
	open: () => boolean

	/**
	 * Function to map item data to form data
	 */
	mapToForm: (item: TData | null) => TFormData

	/**
	 * Optional validation function
	 * Return an object with field errors, or empty object if valid
	 */
	validate?: (form: TFormData) => Record<string, string>
}

export function useDialogForm<TData, TFormData extends Record<string, any>>(
	options: UseDialogFormOptions<TData, TFormData>,
) {
	const form = ref<TFormData>({} as TFormData)
	const errors = ref<Record<string, string>>({})
	const isSubmitting = ref(false)

	// Computed flags
	const isEditMode = computed(() => options.item() !== null)
	const isValid = computed(() => Object.keys(errors.value).length === 0)

	// Reset form when dialog opens/closes or item changes
	watch(
		() => [options.open(), options.item()],
		() => {
			form.value = options.mapToForm(options.item())
			errors.value = {}
		},
		{ deep: true, immediate: true },
	)

	// Validation function
	function validateForm(): boolean {
		errors.value = {}

		if (options.validate) {
			errors.value = options.validate(form.value)
		}

		return Object.keys(errors.value).length === 0
	}

	// Clear specific field error
	function clearError(field: keyof TFormData) {
		delete errors.value[field as string]
	}

	// Set specific field error
	function setError(field: keyof TFormData, message: string) {
		errors.value[field as string] = message
	}

	// Reset form to initial state
	function reset() {
		form.value = options.mapToForm(options.item())
		errors.value = {}
		isSubmitting.value = false
	}

	return {
		// State
		form,
		errors,
		isSubmitting,

		// Computed
		isEditMode,
		isValid,

		// Methods
		validateForm,
		clearError,
		setError,
		reset,
	}
}
