<script setup lang="ts">
import { ref } from 'vue'
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AuthLayout from '@/layouts/AuthLayout.vue'

const pin = ref('')
const pinConfirmation = ref('')
</script>

<template>
  <AuthLayout
    title="Set your PIN"
    description="Your account was set up with a temporary PIN. Choose a new 6-digit PIN before continuing."
  >
    <Head title="Set your PIN" />

    <Form
      action="/change-pin"
      method="post"
      reset-on-error
      @error="pin = ''; pinConfirmation = ''"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <input type="hidden" name="pin" :value="pin" />
      <input type="hidden" name="pin_confirmation" :value="pinConfirmation" />

      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="pin">New PIN</Label>
          <div class="flex justify-center">
            <InputOTP
              id="pin"
              v-model="pin"
              :maxlength="6"
              :disabled="processing"
              autofocus
            >
              <InputOTPGroup>
                <InputOTPSlot v-for="index in 6" :key="index" :index="index - 1" />
              </InputOTPGroup>
            </InputOTP>
          </div>
          <InputError :message="errors.pin" />
        </div>

        <div class="grid gap-2">
          <Label for="pin_confirmation">Confirm PIN</Label>
          <div class="flex justify-center">
            <InputOTP
              id="pin_confirmation"
              v-model="pinConfirmation"
              :maxlength="6"
              :disabled="processing"
            >
              <InputOTPGroup>
                <InputOTPSlot v-for="index in 6" :key="index" :index="index - 1" />
              </InputOTPGroup>
            </InputOTP>
          </div>
          <InputError :message="errors.pin_confirmation" />
        </div>

        <Button
          type="submit"
          class="mt-4 w-full"
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
