
export function daysOverdue(scheduledDate: string | null): number {
	if (!scheduledDate) return 0

	const diff = new Date().setHours(0, 0, 0, 0) - new Date(scheduledDate).getTime()

	return Math.max(0, Math.floor(diff / (1000 * 60 * 60 * 24)))
}

export function isDueToday(scheduledDate: string | null): boolean {
	return daysOverdue(scheduledDate) === 0
}

export function urgencyClass(days: number): string {
	if (days >= 3) return 'text-red-600 dark:text-red-400'
	if (days >= 1) return 'text-amber-600 dark:text-amber-400'
	return 'text-yellow-600 dark:text-yellow-400'
}

export function urgencyLabel(days: number): string {
	if (days <= 0) return 'Due today'
	if (days === 1) return 'Overdue 1 day'
	return `Overdue ${days} days`
}

export function usePostItemUrgency() {
	return { daysOverdue, isDueToday, urgencyClass, urgencyLabel }
}
