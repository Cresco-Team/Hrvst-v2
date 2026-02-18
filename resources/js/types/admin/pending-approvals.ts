export interface PendingUser {
  id: number
  name: string
  email: string
  phone_number: string
  image_path: string | null
}

export interface PendingFarmer {
  id: number
  user: PendingUser
  location: {
    province: string
    municipality: string
    barangay: string
    full_address: string
    coordinates: { lat: number; lng: number }
  }
  farm_image: string | null
  submitted_at: string
  submitted_at_human: string
}

export interface PendingDealer {
  id: number
  user: PendingUser
  document_image: string | null
  submitted_at: string
  submitted_at_human: string
}