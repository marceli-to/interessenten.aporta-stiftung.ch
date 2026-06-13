const dateFormat = new Intl.DateTimeFormat('de-CH', {
	day: '2-digit',
	month: '2-digit',
	year: 'numeric',
})

const moneyFormat = new Intl.NumberFormat('de-CH', {
	minimumFractionDigits: 2,
	maximumFractionDigits: 2,
})

export function fmtDate(iso) {
	return iso ? dateFormat.format(new Date(iso)) : '–'
}

export function fmtMoney(value) {
	return value == null ? '–' : moneyFormat.format(Number(value))
}

export function fmtList(items) {
	return items?.length ? items.join(', ') : '–'
}

export function fmtYesNo(value) {
	return value == null ? '–' : value ? 'Ja' : 'Nein'
}

// Phones are stored in E.164 (e.g. "+41794094927"). For display we group the
// digits the Swiss way -> "+41 79 409 49 27". Swiss numbers (+41, 9 national
// digits) get the canonical grouping; any other / non-E.164 value is shown as
// stored so foreign or unparseable numbers are never mangled.
export function fmtPhone(value) {
	if (!value) return '–'

	const swiss = /^\+41(\d{9})$/.exec(value.replace(/\s+/g, ''))
	if (!swiss) return value

	const [, d] = swiss
	return `+41 ${d.slice(0, 2)} ${d.slice(2, 5)} ${d.slice(5, 7)} ${d.slice(7, 9)}`
}
