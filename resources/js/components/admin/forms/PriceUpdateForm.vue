<script setup lang="ts">
import { Form } from '@inertiajs/vue3'
import { PhilippinePeso, Plus } from 'lucide-vue-next'
import { store } from '@/actions/App/Http/Controllers/Admin/VarietyPriceController'
import ResponsiveModal from '@/components/templates/ResponsiveModal.vue'
import { Button } from '@/components/ui/button'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { Label } from '@/components/ui/label'
import Spinner from '@/components/ui/spinner/Spinner.vue'
import type { VarietyResource } from '@/types'

const props = defineProps<{
  open: boolean
  variety: VarietyResource
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  'update:open': [value: boolean]
  submit: [payload: FormData]
}>()

const handleClose = () => {
  emit('update:open', false)
}

</script>

<template>
  <ResponsiveModal :open="open" title="Update Price"
    :description="`Suggested pricing for ${variety.vegetable?.name} ${variety?.name}.`">

    <Form :action="store(props.variety.id)" method="post" :data="{ price_min: 0, price_max: 0 }"
      #default="{ reset, clearErrors, errors, processing }" :options="{ preserveScroll: true, preserveState: true }"
      @success="handleClose" reset-on-success class="space-y-4">

      <div class="grid grid-cols-2 gap-5">
        <div class="space-y-2">
          <Label for="ph_price_min">Minimum Price</Label>
          <InputGroup>
            <InputGroupAddon>
              <PhilippinePeso />
            </InputGroupAddon>

            <InputGroupInput id="ph_price_min" name="price_min" type="text"
              :default-value="variety.latest_price?.price_min" :disabled="processing" class="font-mono font-semibold">
            </InputGroupInput>
          </InputGroup>

          <div v-if="errors.price_min" class="text-xs text-red-500">{{ errors.price_min }}</div>
        </div>

        <div class="space-y-2">
          <Label for="ph_price_max">Maximum Price</Label>
          <InputGroup>
            <InputGroupAddon>
              <PhilippinePeso />
            </InputGroupAddon>

            <InputGroupInput id="ph_price_max" name="price_max" type="text"
              :default-value="variety.latest_price?.price_max" :disabled="processing" class="font-mono font-semibold">
            </InputGroupInput>
          </InputGroup>

          <div v-if="errors.price_max" class="text-xs text-red-500">{{ errors.price_max }}</div>
        </div>
      </div>

      <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end px-6 sm:p-0 pb-6 sm:pb-0">
        <Button type="button" @click="reset(); clearErrors(); handleClose();" :disabled="processing" variant="outline"
          class="cursor-pointer">
          Cancel
        </Button>
        <Button type="submit" :disabled="processing" class="cursor-pointer">
          <Spinner v-if="processing" />
          <Plus v-else />
          Update
        </Button>
      </div>
    </Form>
  </ResponsiveModal>
</template>
