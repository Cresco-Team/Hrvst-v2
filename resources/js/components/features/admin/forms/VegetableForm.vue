<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Leaf } from 'lucide-vue-next'
import { watch } from 'vue'
import { store, update } from '@/routes/admin/vegetables'
import DialogForm from '@/components/dialogs/DialogForm.vue'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import ImageUpload from '@/components/forms/ImageUpload.vue'
import type { VegetableTableRow } from '@/types/resources/product'

const props = defineProps<{
    open: boolean
    vegetable: VegetableTableRow | null
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
        // Category is contextual, inherited from the page route
        // (admin/categories -> admin/vegetables?category=X). Never editable here.
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
        :description="isEditMode()
            ? 'Update the vegetable, variety, or image.'
            : `Create a vegetable under ${categoryName}. Leave variety blank for a generic entry.`"
        :submit-label="isEditMode() ? 'Save Changes' : 'Create Vegetable'"
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
                    <Label>Category</Label>
                    <div class="flex h-9 items-center rounded-md border border-input bg-muted/50 px-3 text-sm text-muted-foreground">
                        {{ categoryName }}
                    </div>
                    <p class="text-xs text-muted-foreground">Set by the category you're currently browsing.</p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="veg-name">
                        Vegetable Name
                        <Badge variant="secondary" class="text-xs font-normal">Required</Badge>
                    </Label>
                    <Input
                        id="veg-name"
                        v-model="form.vegetable_name"
                        placeholder="e.g. Tomato, Cabbage, Carrot"
                        :class="{ 'border-destructive': form.errors.vegetable_name }"
                    />
                    <p v-if="form.errors.vegetable_name" class="text-xs text-destructive">
                        {{ form.errors.vegetable_name }}
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="veg-variety">Variety <span class="text-muted-foreground font-normal">(optional)</span></Label>
                    <Input
                        id="veg-variety"
                        v-model="form.variety_name"
                        placeholder="e.g. Cherry, Roma — leave blank for generic"
                        :class="{ 'border-destructive': form.errors.variety_name }"
                    />
                    <p v-if="form.errors.variety_name" class="text-xs text-destructive">
                        {{ form.errors.variety_name }}
                    </p>
                </div>

                <div class="flex flex-col gap-2">
                    <Label for="veg-local">Local Name <span class="text-muted-foreground font-normal">(optional)</span></Label>
                    <Input id="veg-local" v-model="form.local_name" placeholder="Regional / vernacular name" />
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
