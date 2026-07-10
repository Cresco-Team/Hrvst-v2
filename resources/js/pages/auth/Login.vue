<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { Phone } from '@lucide/vue'
import { ref } from 'vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AuthBase from '@/layouts/AuthLayout.vue'
import { store } from '@/routes/login'

defineProps<{
  status?: string
}>()

const pin = ref('')
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
            v-slot="{ errors, processing }"
            v-bind="{ action: store.url(), method: 'post' }"
            class="flex flex-col gap-6"
            @error="pin = ''"
        >
            <input
                type="hidden"
                name="password"
                :value="pin"
            />

            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="phone_number">Phone Number</Label>
                    <InputGroup>
                        <InputGroupInput
                            id="phone_number"
                            type="tel"
                            name="phone_number"
                            required
                            autofocus
                            :tabindex="1"
                            autocomplete="tel"
                            placeholder="09*********"
                        />
                        <InputGroupAddon>
                            <Phone />
                        </InputGroupAddon>
                    </InputGroup>
                    <InputError :message="errors.phone_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="pin">PIN</Label>
                    <div class="flex justify-center">
                        <InputOTP
                            id="pin"
                            v-model="pin"
                            :maxlength="6"
                            :disabled="processing"
                            :tabindex="2"
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    v-for="index in 6"
                                    :key="index"
                                    :index="index - 1"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
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
