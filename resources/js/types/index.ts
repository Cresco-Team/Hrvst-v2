export * from './auth'
export * from './navigation'
export * from './ui'

import type { Auth } from './auth'

export type FlashType = 'success' | 'error' | 'warning' | 'info'

export type FlashMessage = {
	success: string | null
	error: string | null
	warning: string | null
	info: string | null
}

export type AppPageProps<
	T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
	name: string
	auth: Auth
	sidebarOpen: boolean
	flash: FlashMessage
	[key: string]: unknown
}
