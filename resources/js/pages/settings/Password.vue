<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3'
import { ref } from 'vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp'
import { Label } from '@/components/ui/label'
import { Spinner } from '@/components/ui/spinner'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import type { BreadcrumbItem } from '@/types'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Password settings', href: '/settings/password' },
]

const currentPin = ref('')
const pin = ref('')
const pinConfirmation = ref('')

function resetFields() {
    currentPin.value = ''
    pin.value = ''
    pinConfirmation.value = ''
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Password settings" />

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    title="Update PIN"
                    description="Change the 6-digit PIN used to sign in to your account."
                />

                <Form
                    v-slot="{ errors, processing, recentlySuccessful }"
                    action="/settings/password"
                    method="put"
                    reset-on-success
                    class="flex flex-col gap-6"
                    @error="resetFields"
                    @success="resetFields"
                >
                    <div class="grid gap-2">
                        <Label for="current_password">Current PIN</Label>
                        <div class="flex justify-start">
                            <InputOTP
                                id="current_password"
                                v-model="currentPin"
                                :maxlength="6"
                                :disabled="processing"
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
                        <input
                            type="hidden"
                            name="current_password"
                            :value="currentPin"
                        />
                        <InputError :message="errors.current_password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">New PIN</Label>
                        <div class="flex justify-start">
                            <InputOTP
                                id="password"
                                v-model="pin"
                                :maxlength="6"
                                :disabled="processing"
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
                        <input
                            type="hidden"
                            name="password"
                            :value="pin"
                        />
                        <InputError :message="errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password_confirmation">Confirm New PIN</Label>
                        <div class="flex justify-start">
                            <InputOTP
                                id="password_confirmation"
                                v-model="pinConfirmation"
                                :maxlength="6"
                                :disabled="processing"
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
                        <input
                            type="hidden"
                            name="password_confirmation"
                            :value="pinConfirmation"
                        />
                        <InputError :message="errors.password_confirmation" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            type="submit"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            Save PIN
                        </Button>

                        <p
                            v-if="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            Saved.
                        </p>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>