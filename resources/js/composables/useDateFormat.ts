
export function toInputDate(dateStr: string | null | undefined): string {
	if (!dateStr) return ''

	const d = new Date(dateStr)
	if (isNaN(d.getTime())) return ''

	const year = d.getFullYear()
	const month = String(d.getMonth() + 1).padStart(2, '0')
	const day = String(d.getDate()).padStart(2, '0')

	return `${year}-${month}-${day}`
}

export function useDateFormat() {
	return { toInputDate }
}
