<script setup lang="ts">
import { ref, watch } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AppLayout from '@/layouts/AppLayout.vue'
import admin from '@/routes/admin'
import { storeDealer } from '@/actions/App/Http/Controllers/Admin/UserController'
import type { BreadcrumbItem, FlashMessage } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Admin', href: admin.dashboard().url },
  { title: 'Dealers', href: admin.dealers.index().url },
  { title: 'Add Dealer' },
]

// ─── Form ─────────────────────────────────────────────────────────────────────

const form = useForm({
  name: '',
  phone_number: '',
  email: '',
  document: null as File | null,
})

// ─── PIN modal ────────────────────────────────────────────────────────────────

const page = usePage()
const pinModalOpen = ref(false)
const revealedPin = ref('')

watch(
  () => page.props.flash as FlashMessage | null,
  (flash) => {
    if (flash?.type === 'pin' && flash.pin) {
      revealedPin.value = flash.pin
      pinModalOpen.value = true
    }
  },
  { immediate: true },
)

function onPinModalClose() {
  pinModalOpen.value = false
  revealedPin.value = ''
  form.reset()
}

// ─── Submit ───────────────────────────────────────────────────────────────────

function submit() {
  form.post(storeDealer.url(), { forceFormData: true })
}
</script>

<template>
  <Head title="Add Dealer" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="flex flex-col gap-6 p-4 lg:p-6">
      <Heading
        title="Add Dealer"
        description="Verify the dealer's physical ID and business documents before registering their account."
      />

      <form class="max-w-xl space-y-6" @submit.prevent="submit">
        <!-- Name -->
        <div class="grid gap-2">
          <Label for="name">Full Name</Label>
          <Input
            id="name"
            v-model="form.name"
            type="text"
            placeholder="Maria Santos"
            required
          />
          <InputError :message="form.errors.name" />
        </div>

        <!-- Phone -->
        <div class="grid gap-2">
          <Label for="phone_number">Phone Number</Label>
          <Input
            id="phone_number"
            v-model="form.phone_number"
            type="tel"
            placeholder="09171234567"
            required
          />
          <InputError :message="form.errors.phone_number" />
        </div>

        <!-- Email (optional) -->
        <div class="grid gap-2">
          <Label for="email">
            Email
            <span class="text-muted-foreground font-normal">(optional)</span>
          </Label>
          <Input
            id="email"
            v-model="form.email"
            type="email"
            placeholder="maria@example.com"
          />
          <InputError :message="form.errors.email" />
        </div>

        <!-- Business document (optional) -->
        <div class="grid gap-2">
          <Label for="document">
            Supporting Document
            <span class="text-muted-foreground font-normal">(optional)</span>
          </Label>
          <Input
            id="document"
            type="file"
            accept="image/jpeg,image/jpg,image/png,image/webp"
            @change="(e: Event) => {
              const input = e.target as HTMLInputElement
              form.document = input.files?.[0] ?? null
            }"
          />
          <p class="text-xs text-muted-foreground">
            Business permit, valid ID, or any verification document. Max 5 MB.
          </p>
          <InputError :message="form.errors.document" />
        </div>

        <div class="flex gap-3">
          <Button type="submit" :disabled="form.processing">
            <Spinner v-if="form.processing" />
            Create Dealer
          </Button>
        </div>
      </form>
    </div>
  </AppLayout>

  <!-- PIN reveal modal -->
  <Dialog :open="pinModalOpen" @update:open="!$event && onPinModalClose()">
    <DialogContent
      class="sm:max-w-sm"
      @pointer-down-outside.prevent
      @escape-key-down.prevent
    >
      <DialogHeader class="items-center text-center">
        <DialogTitle>Dealer Created</DialogTitle>
        <DialogDescription>
          Share this temporary PIN with the dealer in person.
          It will not be shown again.
        </DialogDescription>
      </DialogHeader>

      <div class="flex flex-col items-center gap-3 py-6">
        <p class="text-sm text-muted-foreground">Temporary PIN</p>
        <p class="font-mono text-7xl font-bold tracking-[0.5em]">
          {{ revealedPin }}
        </p>
        <p class="text-xs text-muted-foreground text-center max-w-[220px]">
          The dealer will be asked to set a new PIN on their first login.
        </p>
      </div>

      <Button class="w-full" @click="onPinModalClose">Done</Button>
    </DialogContent>
  </Dialog>
</template>
