import type { InertiaLinkProps } from '@inertiajs/vue3'
import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export function toUrl(href: NonNullable<InertiaLinkProps['href']>) {
    return typeof href === 'string' ? href : href?.url
}

export const useCapitalize = (text: string): string => {
    return text.charAt(0).toUpperCase() + text.slice(1)
}
