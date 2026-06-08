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
