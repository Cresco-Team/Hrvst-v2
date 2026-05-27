<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Leaf } from 'lucide-vue-next'
import { watch } from 'vue'
import { update } from '@/routes/admin/vegetables'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import type { VarietyTableRow } from '@/types/resources/product'
import ImageUpload from '@/components/forms/ImageUpload.vue'

const props = defineProps<{
    open: boolean
    vegetable: VarietyTableRow | null
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    success: []
}>()

const form = useForm({
    category_id: '',
    name: '',
    image: null as File | null,
})

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return
        form.category_id = props.vegetable?.category?.id?.toString() ?? ''
        form.name = props.vegetable?.name ?? ''
        form.image = null
        form.clearErrors()
    },
)

function handleSubmit(): void {
    if (!props.vegetable) return
    form.put(update(props.vegetable.id).url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('success'),
    })
}
</script>

<template>
    <DialogForm
        :open="open"
        :form="form"
        title="Edit Vegetable"
        description="Update the vegetable name or image."
        submit-label="Save Changes"
        max-width="md"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <Leaf class="size-5 text-primary" />
        </template>

        <template #default>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <Label
                        for="update-veg-name"
                        class="flex items-center gap-1.5"
                    >
                        Name
                        <Badge variant="secondary" class="text-xs font-normal"
                            >Required</Badge
                        >
                    </Label>
                    <Input
                        id="update-veg-name"
                        v-model="form.name"
                        placeholder="e.g. Pechay, Kangkong, Carrot"
                        :class="{ 'border-destructive': form.errors.name }"
                    />
                    <p v-if="form.errors.name" class="text-xs text-destructive">
                        {{ form.errors.name }}
                    </p>
                </div>

                <ImageUpload
                    v-model="form.image"
                    :existing-image-url="props.vegetable?.image_url"
                    :error="form.errors.image"
                    :required="false"
                />
            </div>
        </template>
    </DialogForm>
</template>
