<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AuthLayout from '@/layouts/AuthLayout.vue'
</script>

<template>
  <AuthLayout
    title="Set your PIN"
    description="Your account was set up with a temporary PIN. Choose a new 4-digit PIN before continuing."
  >
    <Head title="Set your PIN" />

    <Form
      action="/change-pin"
      method="post"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="pin">New PIN</Label>
          <Input
            id="pin"
            type="password"
            name="pin"
            required
            autofocus
            :tabindex="1"
            autocomplete="new-password"
            placeholder="••••"
            inputmode="numeric"
            maxlength="4"
          />
          <InputError :message="errors.pin" />
        </div>

        <div class="grid gap-2">
          <Label for="pin_confirmation">Confirm PIN</Label>
          <Input
            id="pin_confirmation"
            type="password"
            name="pin_confirmation"
            required
            :tabindex="2"
            autocomplete="new-password"
            placeholder="••••"
            inputmode="numeric"
            maxlength="4"
          />
          <InputError :message="errors.pin_confirmation" />
        </div>

        <Button
          type="submit"
          class="mt-4 w-full"
          :tabindex="3"
          :disabled="processing"
          data-test="change-pin-button"
        >
          <Spinner v-if="processing" />
          Set PIN &amp; Continue
        </Button>
      </div>
    </Form>
  </AuthLayout>
</template>
