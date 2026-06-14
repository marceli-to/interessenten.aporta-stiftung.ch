import { ref, computed, watch } from 'vue'
import { useRoute } from 'vue-router'

/**
 * Multi-select for a filtered, paginated list, scoped to the *current filtered
 * result* — there is no global "select all 677". Selection survives pagination
 * within a filter and is cleared when the filter scope changes.
 *
 * Two modes:
 *  - explicit:     `selected` holds the picked ids (may span pages).
 *  - all-matching: the whole filtered result is in scope and `excluded` holds
 *    the ids the user unticked. The full id list is never materialised client-
 *    side; bulk endpoints resolve it server-side from `selectionPayload()`.
 *
 * `total` is the filtered total (e.g. store.total), `pageIds` the ids on the
 * current page. Returns the state and actions a list view binds to.
 */

// Query keys that define ordering/paging, not *which* rows match.
const NON_SCOPE_KEYS = ['page', 'sort', 'direction']

export function useListSelection({ total, pageIds }) {
	const route = useRoute()

	const filterActive = computed(() =>
		Object.keys(route.query).some((key) => !NON_SCOPE_KEYS.includes(key) && route.query[key] !== '')
	)

	// Order-agnostic signature of the filter scope; changes mean the matching set
	// changed, so any selection is stale.
	const scopeKey = computed(() =>
		Object.keys(route.query)
			.filter((key) => !NON_SCOPE_KEYS.includes(key))
			.sort()
			.map((key) => `${key}=${route.query[key]}`)
			.join('&')
	)

	const selected = ref(new Set())
	const selectAllMatching = ref(false)
	const excluded = ref(new Set())

	function clearSelection() {
		selected.value = new Set()
		selectAllMatching.value = false
		excluded.value = new Set()
	}

	watch(scopeKey, clearSelection)
	watch(filterActive, (active) => { if (!active) clearSelection() })

	const isSelected = (id) =>
		selectAllMatching.value ? !excluded.value.has(id) : selected.value.has(id)

	function toggleRow(id) {
		if (selectAllMatching.value) {
			const next = new Set(excluded.value)
			next.has(id) ? next.delete(id) : next.add(id)
			excluded.value = next
			return
		}
		const next = new Set(selected.value)
		next.has(id) ? next.delete(id) : next.add(id)
		selected.value = next
	}

	const selectedCount = computed(() =>
		selectAllMatching.value
			? Math.max(0, total.value - excluded.value.size)
			: selected.value.size
	)

	// Header checkbox reflects the current page only.
	const pageAllSelected = computed(
		() => pageIds.value.length > 0 && pageIds.value.every((id) => isSelected(id))
	)
	const pageSomeSelected = computed(
		() => !pageAllSelected.value && pageIds.value.some((id) => isSelected(id))
	)

	function toggleAll() {
		if (pageAllSelected.value) {
			if (selectAllMatching.value) {
				excluded.value = new Set([...excluded.value, ...pageIds.value])
			} else {
				const next = new Set(selected.value)
				pageIds.value.forEach((id) => next.delete(id))
				selected.value = next
			}
			return
		}
		if (selectAllMatching.value) {
			const next = new Set(excluded.value)
			pageIds.value.forEach((id) => next.delete(id))
			excluded.value = next
		} else {
			selected.value = new Set([...selected.value, ...pageIds.value])
		}
	}

	// "Alle N auswählen" upgrade: offered once the page is fully ticked and more
	// rows exist beyond it.
	const canSelectAllMatching = computed(
		() => !selectAllMatching.value && pageAllSelected.value && total.value > pageIds.value.length
	)

	function selectAll() {
		selected.value = new Set()
		excluded.value = new Set()
		selectAllMatching.value = true
	}

	// Either explicit ids, or — for all-matching — the active filter params (flat,
	// same names as the list query) plus exclusions, which the backend resolves
	// through the shared filter parsing.
	function selectionPayload() {
		if (selectAllMatching.value) {
			const filters = { ...route.query }
			NON_SCOPE_KEYS.forEach((key) => delete filters[key])
			return { ...filters, exclude: [...excluded.value] }
		}
		return { ids: [...selected.value] }
	}

	return {
		filterActive,
		selected,
		selectAllMatching,
		isSelected,
		toggleRow,
		toggleAll,
		selectAll,
		canSelectAllMatching,
		selectedCount,
		pageAllSelected,
		pageSomeSelected,
		clearSelection,
		selectionPayload,
	}
}
