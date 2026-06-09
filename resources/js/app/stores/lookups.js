import { defineStore } from 'pinia'
import api from '@/api/lookups'

// Reference sets ({ slug, label, sort_order, active }) used to populate edit-mode
// selects and to resolve slug → label for read mode. Fetched once per session.
export const useLookupsStore = defineStore('lookups', {
	state: () => ({
		data: {},
		loaded: false,
	}),
	actions: {
		async fetch() {
			if (this.loaded) return
			const { data } = await api.show()
			this.data = data
			this.loaded = true
		},
	},
	getters: {
		// `{ value, label }[]` for the form Select component, active entries only.
		options: (state) => (key) =>
			(state.data[key] ?? [])
				.filter((o) => o.active !== false)
				.map((o) => ({ value: o.slug, label: o.label })),

		// Resolve a single slug to its label (falls back to the slug, then a dash).
		label: (state) => (key, slug) =>
			(state.data[key] ?? []).find((o) => o.slug === slug)?.label ?? slug ?? '–',
	},
})
