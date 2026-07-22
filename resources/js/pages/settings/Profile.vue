<script setup lang="ts">
import { Form, Head, useForm, usePage } from '@inertiajs/vue3'
import { Camera, Phone } from 'lucide-vue-next'
import { ref } from 'vue'
import DeleteUser from '@/components/DeleteUser.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { getInitials } from '@/composables/useInitials'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import type { BreadcrumbItem } from '@/types'
import { edit } from '@/routes/profile'
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController'

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Profile settings', href: edit().url }]

const page = usePage()
const user = page.props.auth.user

// ── Avatar ────────────────────────────────────────────────────────────────────

const avatarInput = ref<HTMLInputElement | null>(null)
const avatarPreview = ref<string | null>(user.avatar ?? null)
const avatarForm = useForm({ avatar: null as File | null })

function handleAvatarChange(event: Event): void {
	const file = (event.target as HTMLInputElement).files?.[0]
	if (!file) return

	avatarForm.avatar = file
	avatarPreview.value = URL.createObjectURL(file)

	avatarForm.post(ProfileController.updateAvatar.url(), {
		forceFormData: true,
		onSuccess: () => avatarForm.reset(),
		onError: () => {
			avatarPreview.value = user.avatar ?? null
			avatarForm.reset()
		},
	})
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <h1 class="sr-only">Profile Settings</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-10">

                <!-- Avatar -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Avatar"
                        description="Click your avatar to upload a new photo"
                    />

                    <button
                        type="button"
                        class="group relative size-20 cursor-pointer rounded-full"
                        :disabled="avatarForm.processing"
                        @click="avatarInput?.click()"
                    >
                        <Avatar class="size-20">
                            <AvatarImage
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                :alt="user.name"
                            />
                            <AvatarFallback class="text-xl font-semibold">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>

                        <div class="absolute inset-0 flex items-center justify-center rounded-full bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                            <Camera class="size-6 text-white" />
                        </div>
                    </button>

                    <input
                        ref="avatarInput"
                        type="file"
                        class="hidden"
                        accept="image/jpeg,image/jpg,image/png,image/webp"
                        @change="handleAvatarChange"
                    />

                    <InputError :message="avatarForm.errors.avatar" />
                </div>

                <Separator />

                <!-- Profile info -->
                <div class="space-y-4">
                    <Heading
                        variant="small"
                        title="Profile information"
                        description="Update your name and email address"
                    />

                    <Form
                        v-slot="{ errors, processing, recentlySuccessful }"
                        v-bind="ProfileController.update()"
                        class="space-y-4"
                        :options="{ preserveScroll: true }"
                    >
                        <div class="grid gap-2">
                            <Label for="name">Name</Label>
                            <Input
                                id="name"
                                name="name"
                                type="text"
                                :default-value="user.name"
                                autocomplete="name"
                                placeholder="Your full name"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">
                                Email
                                <span class="text-muted-foreground font-normal text-xs">(optional)</span>
                            </Label>
                            <Input
                                id="email"
                                name="email"
                                type="email"
                                :default-value="user.email ?? ''"
                                autocomplete="email"
                                placeholder="your@email.com"
                            />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Phone number</Label>
                            <div class="flex h-9 items-center gap-2 rounded-md border bg-muted/40 px-3 text-sm text-muted-foreground">
                                <Phone class="size-4 shrink-0" />
                                {{ user.phone_number ?? '—' }}
                            </div>
                            <p class="text-xs text-muted-foreground">
                                Contact your administrator to change your phone number.
                            </p>
                        </div>

                        <div class="flex items-center gap-4">
                            <Button :disabled="processing">Save</Button>

                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p
                                    v-show="recentlySuccessful"
                                    class="text-sm text-muted-foreground"
                                >
                                    Saved.
                                </p>
                            </Transition>
                        </div>
                    </Form>
                </div>

                <Separator />

                <!-- Delete account -->
                <DeleteUser />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
