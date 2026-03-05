<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3'
import { computed, reactive, ref, watch } from 'vue'
import FarmLocationPicker from '@/components/auth/FarmLocationPicker.vue'
import InputError from '@/components/InputError.vue'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Spinner } from '@/components/ui/spinner'
import AuthLayout from '@/layouts/AuthLayout.vue'
import { login } from '@/routes'
import { store } from '@/routes/register'

interface Municipality {
    id: number
    name: string
    latitude: number
    longitude: number
}

interface Barangay {
    id: number
    name: string
}

const props = defineProps<{
    municipalities: Municipality[]
}>()

// ---------------------------------------------------------------------------
// Form state
// ---------------------------------------------------------------------------

const form = useForm({
    role: '' as 'farmer' | 'dealer' | '',
    name: '',
    email: '',
    phone_number: '',
    password: '',
    password_confirmation: '',
    profile_image: null as File | null,
    // Farmer-specific
    province_id: 1,
    municipality_id: null as number | null,
    barangay_id: null as number | null,
    latitude: null as number | null,
    longitude: null as number | null,
    farm_image: null as File | null,
    // Dealer-specific
    document_image: null as File | null,
})

// ---------------------------------------------------------------------------
// Image previews
// Using a single reactive object instead of individual refs passed as args.
// Vue 3 auto-unwraps refs in templates — passing a ref as an argument gives
// you the VALUE (null), not the ref itself, making .value throw on null.
// ---------------------------------------------------------------------------

const previews = reactive<Record<'profile_image' | 'farm_image' | 'document_image', string | null>>({
    profile_image: null,
    farm_image: null,
    document_image: null,
})

function handleFileSelect(
    event: Event,
    field: 'profile_image' | 'farm_image' | 'document_image',
): void {
    const input = event.target as HTMLInputElement
    const file = input.files?.[0] ?? null
    form[field] = file
    if (file) {
        const reader = new FileReader()
        reader.onload = (e) => {
            previews[field] = e.target?.result as string
        }
        reader.readAsDataURL(file)
    } else {
        previews[field] = null
    }
}

// ---------------------------------------------------------------------------
// Cascading address state
// ---------------------------------------------------------------------------

const barangays = ref<Barangay[]>([])
const loadingBarangays = ref(false)

const selectedMunicipality = computed<Municipality | null>(
    () => props.municipalities.find(m => m.id === form.municipality_id) ?? null
)

const mapCenter = computed(() =>
    selectedMunicipality.value
        ? { lat: selectedMunicipality.value.latitude, lng: selectedMunicipality.value.longitude }
        : null
)

async function onMunicipalityChange(value: string): Promise<void> {
    const id = parseInt(value, 10)
    form.municipality_id = id
    form.barangay_id = null
    form.latitude = null
    form.longitude = null
    barangays.value = []

    loadingBarangays.value = true
    try {
        const res = await fetch(`/address/barangays?municipality_id=${id}`)
        if (!res.ok) throw new Error(`Server returned ${res.status}`)
        barangays.value = await res.json()
    } catch (e) {
        console.error('Failed to load barangays:', e)
    } finally {
        loadingBarangays.value = false
    }
}

// Reset profile-specific fields on role switch to avoid stale data in submission
watch(() => form.role, () => {
    form.municipality_id = null
    form.barangay_id = null
    form.latitude = null
    form.longitude = null
    form.farm_image = null
    form.document_image = null
    previews.farm_image = null
    previews.document_image = null
    barangays.value = []
})

// ---------------------------------------------------------------------------
// Submission
// ---------------------------------------------------------------------------

function submit(): void {
    form.post(store.url(), {
        forceFormData: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <AuthLayout
        title="Create an account"
        description="Enter your details below to create your account"
    >
        <Head title="Register" />

        <form class="flex flex-col gap-6" @submit.prevent="submit">

            <!-- Role selection -->
            <div class="grid gap-2">
                <Label>I am registering as</Label>
                <div class="grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="rounded-md border px-4 py-3 text-sm font-medium transition-colors"
                        :class="form.role === 'farmer'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-input text-muted-foreground hover:border-primary/50'"
                        @click="form.role = 'farmer'"
                    >
                        🌱 Farmer
                    </button>
                    <button
                        type="button"
                        class="rounded-md border px-4 py-3 text-sm font-medium transition-colors"
                        :class="form.role === 'dealer'
                            ? 'border-primary bg-primary/5 text-primary'
                            : 'border-input text-muted-foreground hover:border-primary/50'"
                        @click="form.role = 'dealer'"
                    >
                        🏪 Dealer
                    </button>
                </div>
                <InputError :message="form.errors.role" />
            </div>

            <!-- Common fields -->
            <div class="grid gap-4">

                <div class="grid gap-2">
                    <Label for="name">Full name</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        placeholder="Juan dela Cruz"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone_number">Phone number</Label>
                    <Input
                        id="phone_number"
                        v-model="form.phone_number"
                        type="tel"
                        required
                        :tabindex="3"
                        autocomplete="tel"
                        placeholder="09171234567"
                    />
                    <InputError :message="form.errors.phone_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        v-model="form.password"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                    />
                    <InputError :message="form.errors.password_confirmation" />
                </div>

                <!-- Profile image (optional) -->
                <div class="grid gap-2">
                    <Label for="profile_image">
                        Profile photo
                        <span class="ml-1 text-xs text-muted-foreground">(optional)</span>
                    </Label>
                    <div class="flex items-center gap-3">
                        <img
                            v-if="previews.profile_image"
                            :src="previews.profile_image"
                            alt="Profile preview"
                            class="size-12 rounded-full border object-cover"
                        />
                        <Input
                            id="profile_image"
                            type="file"
                            accept="image/jpeg,image/jpg,image/png,image/webp"
                            :tabindex="6"
                            class="cursor-pointer"
                            @change="handleFileSelect($event, 'profile_image')"
                        />
                    </div>
                    <InputError :message="form.errors.profile_image" />
                </div>

            </div>

            <!-- Farmer-specific fields -->
            <template v-if="form.role === 'farmer'">
                <div class="grid gap-4 rounded-md border p-4">
                    <p class="text-sm font-medium">Farm details</p>

                    <div class="grid gap-2">
                        <Label for="municipality_id">Municipality</Label>
                        <Select
                            :model-value="form.municipality_id?.toString() ?? ''"
                            @update:model-value="onMunicipalityChange"
                        >
                            <SelectTrigger id="municipality_id">
                                <SelectValue placeholder="Select municipality" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="m in municipalities"
                                    :key="m.id"
                                    :value="m.id.toString()"
                                >
                                    {{ m.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.municipality_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="barangay_id">Barangay</Label>
                        <Select
                            :model-value="form.barangay_id?.toString() ?? ''"
                            :disabled="!form.municipality_id || loadingBarangays"
                            @update:model-value="v => form.barangay_id = parseInt(v, 10)"
                        >
                            <SelectTrigger id="barangay_id">
                                <SelectValue
                                    :placeholder="loadingBarangays ? 'Loading...' : 'Select barangay'"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="b in barangays"
                                    :key="b.id"
                                    :value="b.id.toString()"
                                >
                                    {{ b.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.barangay_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Farm location</Label>
                        <FarmLocationPicker
                            :municipality-coords="mapCenter"
                            :model-value="{ lat: form.latitude, lng: form.longitude }"
                            :lat-error="form.errors.latitude"
                            :lng-error="form.errors.longitude"
                            @update:model-value="({ lat, lng }) => { form.latitude = lat; form.longitude = lng }"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="farm_image">
                            Farm photo
                            <span class="ml-1 text-xs text-destructive">*</span>
                        </Label>
                        <div class="grid gap-2">
                            <img
                                v-if="previews.farm_image"
                                :src="previews.farm_image"
                                alt="Farm preview"
                                class="h-32 w-full rounded-md border object-cover"
                            />
                            <Input
                                id="farm_image"
                                type="file"
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                                class="cursor-pointer"
                                @change="handleFileSelect($event, 'farm_image')"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            A photo of your farm is required for verification. Max 5MB.
                        </p>
                        <InputError :message="form.errors.farm_image" />
                    </div>

                </div>
            </template>

            <!-- Dealer-specific fields -->
            <template v-if="form.role === 'dealer'">
                <div class="grid gap-4 rounded-md border p-4">
                    <p class="text-sm font-medium">Dealer verification</p>

                    <div class="grid gap-2">
                        <Label for="document_image">
                            Business document photo
                            <span class="ml-1 text-xs text-destructive">*</span>
                        </Label>
                        <div class="grid gap-2">
                            <img
                                v-if="previews.document_image"
                                :src="previews.document_image"
                                alt="Document preview"
                                class="h-32 w-full rounded-md border object-cover"
                            />
                            <Input
                                id="document_image"
                                type="file"
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                                class="cursor-pointer"
                                @change="handleFileSelect($event, 'document_image')"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            A photo of your business permit or valid ID is required. Max 5MB.
                        </p>
                        <InputError :message="form.errors.document_image" />
                    </div>

                </div>
            </template>

            <!-- Submit -->
            <Button
                type="submit"
                class="w-full"
                :tabindex="7"
                :disabled="form.processing || form.role === ''"
                data-test="register-user-button"
            >
                <Spinner v-if="form.processing" />
                Create account
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                Already have an account?
                <TextLink :href="login()" :tabindex="8">Log in</TextLink>
            </div>

        </form>

    </AuthLayout>
</template>
