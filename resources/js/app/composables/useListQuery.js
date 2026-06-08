import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'

/**
 * Drives a paginated, searchable, sortable list off the URL query, so the list
 * state (page / search / sort) survives navigating away and back and is
 * shareable / bookmarkable.
 *
 * `fetch` is called with { page, perPage, search, sort, direction } whenever the
 * URL changes; wire it to a store action. Returns the bits a list view binds to:
 * `search` (v-model), `sort` / `direction` (header indicators), `toggleSort` and
 * `goToPage`.
 */
export function useListQuery({ fetch, perPage = 25, defaultSort = 'opened_at', defaultDirection = 'desc' }) {
	const route = useRoute()
	const router = useRouter()

	const sort = computed(() => route.query.sort ?? defaultSort)
	const direction = computed(() => route.query.direction ?? defaultDirection)
	const search = ref(route.query.search ?? '')

	function load() {
		fetch({
			page: Number(route.query.page) || 1,
			perPage,
			search: route.query.search ?? '',
			sort: sort.value,
			direction: direction.value,
		})
	}

	// Watch fullPath (a primitive) rather than route.query: the query object's
	// reference isn't reliably seen as changed by watch, so a plain query watch
	// wouldn't refetch on sort / page / search changes.
	watch(() => route.fullPath, load, { immediate: true })

	function goToPage(page) {
		router.push({ query: { ...route.query, page } })
	}

	function toggleSort(column) {
		const dir = sort.value === column && direction.value === 'asc' ? 'desc' : 'asc'
		router.push({ query: { ...route.query, sort: column, direction: dir, page: 1 } })
	}

	// Search: debounced, resets to page 1. Kept in sync with the URL so back /
	// forward navigation restores the term in the input.
	let searchTimer
	watch(search, (value) => {
		if (value === (route.query.search ?? '')) return
		clearTimeout(searchTimer)
		searchTimer = setTimeout(() => {
			const query = { ...route.query, page: 1 }
			if (value) query.search = value
			else delete query.search
			router.push({ query })
		}, 300)
	})

	watch(() => route.query.search, (value) => {
		search.value = value ?? ''
	})

	onBeforeUnmount(() => clearTimeout(searchTimer))

	return { sort, direction, search, goToPage, toggleSort }
}
