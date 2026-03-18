<script setup lang="ts">
import { Image as ImageIcon, Upload, X } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'

interface Props {
	modelValue: File | null
	existingImageUrl?: string | null
	error?: string
	required?: boolean
}

const props = withDefaults(defineProps<Props>(), {
	existingImageUrl: null,
	error: '',
	required: false,
})

const emit = defineEmits<{
	'update:modelValue': [file: File | null]
}>()

const fileInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const previewUrl = ref<string | null>(props.existingImageUrl)

// Watch for file changes to update preview
watch(
	() => props.modelValue,
	(newFile) => {
		if (newFile) {
			const reader = new FileReader()
			reader.onload = (e) => {
				previewUrl.value = e.target?.result as string
			}
			reader.readAsDataURL(newFile)
		} else if (!props.existingImageUrl) {
			previewUrl.value = null
		}
	},
)

// Reset preview to existing image when cleared
watch(
	() => props.existingImageUrl,
	(url) => {
		if (!props.modelValue) {
			previewUrl.value = url
		}
	},
)

const hasImage = computed(() => previewUrl.value !== null)

function handleFileSelect(event: Event) {
	const input = event.target as HTMLInputElement
	const file = input.files?.[0]

	if (file) {
		validateAndEmit(file)
	}
}

function handleDrop(event: DragEvent) {
	isDragging.value = false
	const file = event.dataTransfer?.files[0]

	if (file) {
		validateAndEmit(file)
	}
}

function validateAndEmit(file: File) {
	// Basic client-side validation
	const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp']
	const maxSize = 5 * 1024 * 1024 // 5MB

	if (!validTypes.includes(file.type)) {
		alert('Please upload a valid image file (JPEG, PNG, or WebP)')
		return
	}

	if (file.size > maxSize) {
		alert('Image must be less than 5MB')
		return
	}

	emit('update:modelValue', file)
}

function clearImage() {
	previewUrl.value = props.existingImageUrl
	emit('update:modelValue', null)
	if (fileInput.value) {
		fileInput.value.value = ''
	}
}

function triggerFileInput() {
	fileInput.value?.click()
}
</script>

<template>
    <div class="flex flex-col gap-2">
        <Label class="flex items-center gap-1.5">
            <ImageIcon class="size-3.5" />
            Variety Image
            <Badge v-if="required" variant="secondary" class="text-xs font-normal">Required</Badge>
        </Label>

        <!-- Upload Area -->
        <div
            v-if="!hasImage"
            class="relative flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed p-8 transition-colors"
            :class="{
                'border-destructive bg-destructive/5': error,
                'border-primary bg-primary/5': isDragging && !error,
                'border-input hover:border-primary/50': !isDragging && !error,
            }"
            @click="triggerFileInput"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
        >
            <Upload class="size-8 text-muted-foreground" />
            <div class="flex flex-col items-center gap-1 text-center">
                <p class="text-sm font-medium">
                    {{ isDragging ? 'Drop image here' : 'Click to upload or drag and drop' }}
                </p>
                <p class="text-xs text-muted-foreground">
                    JPEG, PNG, or WebP up to 5MB
                </p>
            </div>
            <input
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/jpg,image/webp"
                class="hidden"
                @change="handleFileSelect"
            />
        </div>

        <!-- Preview Area -->
        <div v-else class="relative overflow-hidden rounded-lg border">
            <img
                :src="previewUrl!"
                alt="Variety preview"
                class="h-48 w-full object-cover"
            />
            <div class="absolute inset-0 flex items-center justify-center gap-2 bg-black/50 opacity-0 transition-opacity hover:opacity-100">
                <Button
                    type="button"
                    variant="secondary"
                    size="sm"
                    @click="triggerFileInput"
                >
                    Change
                </Button>
                <Button
                    type="button"
                    variant="destructive"
                    size="sm"
                    @click="clearImage"
                >
                    <X class="mr-1.5 size-4" />
                    Remove
                </Button>
            </div>
            <input
                ref="fileInput"
                type="file"
                accept="image/jpeg,image/png,image/jpg,image/webp"
                class="hidden"
                @change="handleFileSelect"
            />
        </div>

        <!-- Error Message -->
        <p v-if="error" class="text-xs text-destructive">
            {{ error }}
        </p>
        <p v-else class="text-xs text-muted-foreground">
            Image will be automatically optimized and converted to WebP format
        </p>
    </div>
</template>
