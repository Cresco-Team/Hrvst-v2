// Mirrors app/Http/Resources/Profile/UserResource.php

export interface UserResource {
    id: number
    name: string
    email: string
    phone_number: string | null
    avatar_url: string
}
