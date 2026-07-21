// 422 error maps arrive flattened by field path. A panel strips its own section
// prefix (see panels/Editable.vue), which leaves nested keys like
// 'employer.name' for the sub-blocks. scope() narrows such a map to one block
// and hands back bare keys ('name'), so a fields component can be dropped in at
// any depth of the payload without knowing where it sits.
export function scopeErrors(errors, prefix) {
	const head = `${prefix}.`
	const out = {}
	for (const [key, message] of Object.entries(errors ?? {})) {
		if (key.startsWith(head)) out[key.slice(head.length)] = message
	}
	return out
}
