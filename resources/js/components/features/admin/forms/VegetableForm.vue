<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Vegan } from '@lucide/vue'
import { Leaf } from 'lucide-vue-next'
import { watch } from 'vue'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { store, update } from '@/routes/admin/vegetables'
import type { VegetableAdminData } from '@/types/resources/product'

const props = defineProps<{
    open: boolean
    vegetable: VegetableAdminData | null
    categoryId: number
    categoryName: string
}>()

const emit = defineEmits<{
    'update:open': [value: boolean]
    success: []
}>()

const isEditMode = () => props.vegetable !== null

const form = useForm({
    category_id: '',
    vegetable_name: '',
    variety_name: '',
    local_name: '',
    image: null as File | null,
})

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) return
        form.category_id = String(props.categoryId)
        form.vegetable_name = props.vegetable?.vegetable_name ?? ''
        form.variety_name = props.vegetable?.variety_name ?? ''
        form.local_name = props.vegetable?.local_name ?? ''
        form.image = null
        form.clearErrors()
    },
)

function handleSubmit(): void {
    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('success'),
    }

    if (isEditMode()) {
        form.transform((data) => ({ ...data, _method: 'put' }))
            .post(update(props.vegetable!.id).url, options)
    } else {
        form.post(store().url, options)
    }
}
</script>

<template>
    <DialogForm
        :open="open"
        :form="form"
        :title="isEditMode() ? 'Edit Vegetable' : 'Add Vegetable'"
        :submit-label="isEditMode() ? 'Update' : 'Create'"
        max-width="md"
        @update:open="emit('update:open', $event)"
        @submit="handleSubmit"
    >
        <template #icon>
            <Vegan class="size-5 text-primary" />
        </template>

        <template #default>
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <Label for="veg-name">
                        Vegetable Name
                        <Badge
                            variant="destructive"
                            class="text-xs font-normal"
                        >Required</Badge>
                    </Label>
                    <Input
                        id="veg-name"
                        v-model="form.vegetable_name"
                        :class="{ 'border-destructive': form.errors.vegetable_name }"
                        placeholder="..."
                    />
                    <p
                        v-if="form.errors.vegetable_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.vegetable_name }}
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="veg-variety">
                        Variety Name
                        <Badge
                            variant="outline"
                            class="text-xs font-normal"
                        >Optional</Badge>
                    </Label>
                    <Input
                        id="veg-variety"
                        v-model="form.variety_name"
                        :class="{ 'border-destructive': form.errors.variety_name }"
                        placeholder="..."
                    />
                    <p
                        v-if="form.errors.variety_name"
                        class="text-xs text-destructive"
                    >
                        {{ form.errors.variety_name }}
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="veg-local">
                        Local Name
                        <Badge
                            variant="outline"
                            class="text-xs font-normal"
                        >Optional</Badge>
                    </Label>
                    <Input 
                        id="veg-local" 
                        v-model="form.local_name" 
                        placeholder="..."
                    />
                </div>

                <ImageUpload
                    v-model="form.image"
                    :existing-image-url="props.vegetable?.image_url"
                    :error="form.errors.image"
                    :required="!isEditMode()"
                />
            </div>
        </template>
    </DialogForm>
</template>
