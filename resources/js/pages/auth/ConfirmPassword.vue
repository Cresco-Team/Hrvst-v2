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
</script>

<template>
    <AuthLayout
        title="Confirm your PIN"
        description="Please confirm your PIN before continuing to this secure area."
    >
        <Head title="Confirm PIN" />

        <Form
            action="/user/confirm-password"
            method="post"
            reset-on-error
            @error="pin = ''"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <input type="hidden" name="password" :value="pin" />

            <div class="grid gap-2">
                <Label for="pin">PIN</Label>
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
                <InputError :message="errors.password" />
            </div>

            <Button
                type="submit"
                class="w-full"
                :tabindex="2"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Confirm
            </Button>
        </Form>
    </AuthLayout>
</template>
