import { defineStore } from 'pinia'

/**
 * Browse set for the "Resultatansicht": the ordered list of application ids the
 * user chose in the list ("Öffnen"), so the detail view can step prev/next
 * through exactly that selection and show the position ("3 / 12").
 *
 * Holds ids only (not full records) — each application is loaded by the detail
 * view as usual. In-session state: it survives in-app navigation but not a hard
 * refresh, which is fine (the detail view simply hides the browse control then).
 */
export const useBrowseStore = defineStore('browse', {
	state: () => ({
		ids: [],
	}),
	getters: {
		active: (state) => state.ids.length > 0,
		total: (state) => state.ids.length,
		indexOf: (state) => (id) => state.ids.indexOf(Number(id)),
		has: (state) => (id) => state.ids.includes(Number(id)),
	},
	actions: {
		start(ids) {
			this.ids = ids.map(Number)
		},
		clear() {
			this.ids = []
		},
		// 1-based position of an id in the set, or null if it isn't part of it.
		position(id) {
			const i = this.indexOf(id)
			return i === -1 ? null : i + 1
		},
		prevId(id) {
			const i = this.indexOf(id)
			return i > 0 ? this.ids[i - 1] : null
		},
		nextId(id) {
			const i = this.indexOf(id)
			return i !== -1 && i < this.ids.length - 1 ? this.ids[i + 1] : null
		},
	},
})
