export * from './auth'
export * from './enums'
export * from './navigation'
export * from './pages/admin'
export * from './pages/dealer'
export * from './pages/farmer'
export * from './pages/shared'
export * from './resources/marketplace'
export * from './resources/product'
export * from './resources/profile'
export * from './resources/user'
export * from './shared'
export * from './ui'

import type { Auth } from './auth'
import type { FlashMessage } from './shared'

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
	name: string
	auth: Auth
	sidebarOpen: boolean
	flash: FlashMessage
	[key: string]: unknown
}
