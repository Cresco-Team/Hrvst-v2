<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AuthBase from '@/layouts/AuthLayout.vue'
import { store } from '@/routes/login'

defineProps<{
  status?: string
}>()
</script>

<template>
  <AuthBase
    title="Sign in to Hrvst"
    description="Enter your phone number and PIN to continue"
  >
    <Head title="Sign in" />

    <div
      v-if="status"
      class="mb-4 text-center text-sm font-medium text-green-600"
    >
      {{ status }}
    </div>

    <Form
      v-bind="{ action: store.url(), method: 'post' }"
      :reset-on-success="['password']"
      v-slot="{ errors, processing }"
      class="flex flex-col gap-6"
    >
      <div class="grid gap-6">
        <div class="grid gap-2">
          <Label for="phone_number">Phone Number</Label>
          <Input
            id="phone_number"
            type="tel"
            name="phone_number"
            required
            autofocus
            :tabindex="1"
            autocomplete="tel"
            placeholder="09171234567"
          />
          <InputError :message="errors.phone_number" />
        </div>

        <div class="grid gap-2">
          <Label for="password">PIN</Label>
          <Input
            id="password"
            type="password"
            name="password"
            required
            :tabindex="2"
            autocomplete="current-password"
            placeholder="••••"
            inputmode="numeric"
            maxlength="4"
          />
          <InputError :message="errors.password" />
        </div>

        <Button
          type="submit"
          class="mt-4 w-full"
          :tabindex="3"
          :disabled="processing"
          data-test="login-button"
        >
          <Spinner v-if="processing" />
          Sign in
        </Button>
      </div>
    </Form>
  </AuthBase>
</template>
