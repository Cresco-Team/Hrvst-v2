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
        title="Confirm your PIN"
        description="Please confirm your PIN before continuing to this secure area."
    >
        <Head title="Confirm PIN" />

        <Form
            action="/user/confirm-password"
            method="post"
            v-slot="{ errors, processing }"
            class="flex flex-col gap-6"
        >
            <div class="grid gap-2">
                <Label for="password">PIN</Label>
                <Input
                    id="password"
                    type="password"
                    name="password"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="current-password"
                    placeholder="••••"
                    inputmode="numeric"
                    maxlength="4"
                />
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
