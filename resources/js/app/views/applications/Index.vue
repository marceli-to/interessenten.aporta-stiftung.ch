<script setup>
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApplicationsStore } from '@/stores/applications'
import { useListQuery } from '@/composables/useListQuery'
import { useToast } from '@/composables/useToast'
import api from '@/api/applications'
import { fmtDate, fmtMoney, fmtList } from '@/utils/format'
import Panel from '@/components/ui/panels/Display.vue'
import Pagination from '@/components/ui/pagination/Pagination.vue'
import StatusBadge from '@/components/ui/badges/Status.vue'
import TableHeadCell from '@/components/ui/table/HeadCell.vue'
import TableCell from '@/components/ui/table/Cell.vue'
import RowCheckbox from '@/components/ui/table/RowCheckbox.vue'
import BulkActionBar from '@/components/ui/table/BulkActionBar.vue'
import ConfirmDialog from '@/components/ui/dialog/ConfirmDialog.vue'
import Filter from './Filter.vue'

const route = useRoute()
const router = useRouter()
const store = useApplicationsStore()
const toast = useToast()

const { sort, direction, search, goToPage, toggleSort, reload } = useListQuery({
	fetch: store.fetch,
	perPage: 15,
})

// --- Multi-select (filter-scoped) --------------------------------------------
// Bulk actions are bound to the *current filtered result*; there is no global
// "select all 677". The bar only shows when a filter or search is active, and
// the selection survives pagination within that filter but is cleared whenever
// the filter scope changes (a different set of rows now matches).
//
// Two selection modes:
//  - explicit:  `selected` holds the picked ids (opt-in, may span pages).
//  - all-matching: `selectAllMatching` is true and the whole filtered result is
//    in scope; `excluded` holds ids the user unticked. We never materialise the
//    full id list client-side — bulk endpoints resolve it server-side from the
//    same filters the list uses (see store.fetch / GetRequest).

// Query keys that define ordering/paging, not *which* rows match. Selection is
// kept across changes to these, and cleared when anything else changes.
const NON_SCOPE_KEYS = ['page', 'sort', 'direction']

// True when the list is narrowed by a search term or any filter chip.
const filterActive = computed(() =>
	Object.keys(route.query).some((key) => !NON_SCOPE_KEYS.includes(key) && route.query[key] !== '')
)

// 11 data columns, plus the selection checkbox column when the filter is active
// (used for the loading / empty-state colspans).
const columnCount = computed(() => (filterActive.value ? 12 : 11))

// A stable signature of the current filter scope (search + chips, order-agnostic).
// When this changes, the matching set changed, so any selection is stale.
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

function resetSelection() {
	selected.value = new Set()
	selectAllMatching.value = false
	excluded.value = new Set()
}

// Clear when the filter scope changes (not on mere paging / sorting), and also
// whenever the filter is dropped entirely.
watch(scopeKey, resetSelection)
watch(filterActive, (active) => { if (!active) resetSelection() })

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

// Effective count: in all-matching mode it's the filtered total minus exclusions;
// otherwise the size of the explicit set.
const selectedCount = computed(() =>
	selectAllMatching.value
		? Math.max(0, store.total - excluded.value.size)
		: selected.value.size
)

// Header checkbox reflects the *current page* only (it's a per-page toggle).
const pageIds = computed(() => store.applications.map((a) => a.id))
const pageAllSelected = computed(
	() => pageIds.value.length > 0 && pageIds.value.every((id) => isSelected(id))
)
const pageSomeSelected = computed(
	() => !pageAllSelected.value && pageIds.value.some((id) => isSelected(id))
)

function toggleAll() {
	if (pageAllSelected.value) {
		// Deselect the current page. In all-matching mode that means excluding it.
		if (selectAllMatching.value) {
			excluded.value = new Set([...excluded.value, ...pageIds.value])
		} else {
			const next = new Set(selected.value)
			pageIds.value.forEach((id) => next.delete(id))
			selected.value = next
		}
		return
	}
	// Select the current page (explicit mode — the "Alle N" upgrade is separate).
	if (selectAllMatching.value) {
		const next = new Set(excluded.value)
		pageIds.value.forEach((id) => next.delete(id))
		excluded.value = next
	} else {
		selected.value = new Set([...selected.value, ...pageIds.value])
	}
}

// "Alle N auswählen" upgrade: switch to all-matching (whole filtered result),
// resolved server-side. Offered by the bar once the page is fully ticked and
// more rows exist beyond the page.
const canSelectAllMatching = computed(
	() => !selectAllMatching.value && pageAllSelected.value && store.total > pageIds.value.length
)

function selectAll() {
	selected.value = new Set()
	excluded.value = new Set()
	selectAllMatching.value = true
}

const clearSelection = resetSelection

// The payload bulk endpoints receive: either explicit ids, or — for all-matching
// — the active filter params (flat, same names as the list query) plus the
// exclusions. The backend resolves the set server-side through the shared filter
// parsing, so it deletes exactly the rows the filter matched.
function selectionPayload() {
	if (selectAllMatching.value) {
		const filters = { ...route.query }
		NON_SCOPE_KEYS.forEach((key) => delete filters[key])
		return { ...filters, exclude: [...excluded.value] }
	}
	return { ids: [...selected.value] }
}

// --- Bulk delete (confirmed) -------------------------------------------------
// The button opens a ConfirmDialog showing the real count; confirming POSTs the
// selection payload, then clears the selection and refreshes the list in place.
const confirmingDelete = ref(false)
const deleting = ref(false)

const askBulkDelete = () => { confirmingDelete.value = true }

const deleteMessage = computed(() =>
	`${selectedCount.value} ${selectedCount.value === 1 ? 'Bewerbung wird' : 'Bewerbungen werden'} aus der Liste entfernt. `
	+ 'Sie bleiben gespeichert und können später wiederhergestellt werden.'
)

async function handleBulkDelete() {
	deleting.value = true
	try {
		const { data } = await api.bulkDestroy(selectionPayload())
		confirmingDelete.value = false
		clearSelection()
		reload()
		toast.success(`${data.deleted} ${data.deleted === 1 ? 'Bewerbung' : 'Bewerbungen'} gelöscht.`)
	} catch {
		// failure already surfaced as a toast by the axios interceptor
		confirmingDelete.value = false
	} finally {
		deleting.value = false
	}
}

// Placeholder actions — wired up in later steps:
//  - export: Teil A (hängt an §4-Klärung: Felder / Format).
//  - open: Teil B (Resultatansicht — opens the first selected application and
//    enables prev/next browsing through the selection).
function bulkExport() {
	console.log('bulk export', selectionPayload())
}
function bulkOpen() {
	console.log('bulk open (browse)', selectionPayload())
}

// Visual treatment per row. `flagged` (Wichtig) overrides the open/extended
// status; an archived application is terminal and overrides everything.
const styles = {
	opened: { text: 'text-green' },
	extended: { text: 'text-violet' },
	flagged: { text: 'text-red' },
	archived: { text: 'text-gray' },
	knif: { text: 'text-red' },
}

function display(application) {
	let key = application.status.value
	let label = application.status.label

	if (key !== 'archived' && key !== 'knif' && application.flagged) {
		key = 'flagged'
		label = 'Wichtig'
	}

	return { key, label, ...styles[key] }
}

// Carry the current list URL (search / page / sort) into history state so the
// detail view's back link can return to the exact same filtered list.
function open(application) {
	router.push({
		name: 'applications.show',
		params: { id: application.id },
		state: { from: route.fullPath },
	})
}
</script>

<template>
  <Filter v-model:search="search" />


  <Panel>
    <div class="overflow-x-auto">
      <table class="w-full text-sm whitespace-nowrap">
        <thead class="text-left text-black border-b border-blue/20">
          <tr>
            <TableHeadCell v-if="filterActive" variant="first" class="pr-10!">
              <RowCheckbox
                :model-value="pageAllSelected"
                :indeterminate="pageSomeSelected"
                aria-label="Seite auswählen"
                @update:model-value="toggleAll"
              />
            </TableHeadCell>
            <TableHeadCell :variant="filterActive ? null : 'first'" sort-key="reference_number" :sort="sort" :direction="direction" @sort="toggleSort">
              Nr.
            </TableHeadCell>
            <TableHeadCell>
              Hauptmieter
            </TableHeadCell>
            <TableHeadCell sort-key="status" :sort="sort" :direction="direction" @sort="toggleSort">
              Status
            </TableHeadCell>
            <TableHeadCell sort-key="opened_at" :sort="sort" :direction="direction" @sort="toggleSort">
              Angemeldet
            </TableHeadCell>
            <TableHeadCell sort-key="extended_at" :sort="sort" :direction="direction" @sort="toggleSort">
              Verlängert
            </TableHeadCell>
            <TableHeadCell sort-key="earliest_move_in" :sort="sort" :direction="direction" @sort="toggleSort">
              Mietbeginn
            </TableHeadCell>
            <TableHeadCell class="text-right" sort-key="max_gross_rent" :sort="sort" :direction="direction" @sort="toggleSort">
              Max. Miete
            </TableHeadCell>
            <TableHeadCell sort-key="total_persons" :sort="sort" :direction="direction" @sort="toggleSort">
              Pers.
            </TableHeadCell>
            <TableHeadCell>
              Zimmer
            </TableHeadCell>
            <TableHeadCell>
              Kreise
            </TableHeadCell>
            <TableHeadCell variant="last">
              Einkommen
            </TableHeadCell>
          </tr>
        </thead>
        <tbody class="divide-y divide-blue/20">
          <template v-if="store.loading">
            <tr>
              <td :colspan="columnCount">
                Laden …
              </td>
            </tr>
          </template>
          <template v-else>
            <tr
              v-for="application in store.applications"
              :key="application.id"
              class="cursor-pointer align-top"
              :class="isSelected(application.id) ? 'bg-light-blue/60' : 'hover:bg-light-gray/10'"
              @click="open(application)">
              <TableCell v-if="filterActive" variant="first" @click.stop class="pr-10!">
                <RowCheckbox
                  :model-value="isSelected(application.id)"
                  :aria-label="`Bewerbung ${application.reference_number} auswählen`"
                  @update:model-value="toggleRow(application.id)"
                />
              </TableCell>
              <TableCell :variant="filterActive ? null : 'first'" class="font-bold" :class="display(application).text">
                {{ application.reference_number }}
              </TableCell>

              <TableCell>
                <div class="font-bold" :class="display(application).text">
                  {{ application.main_applicant?.first_name }}
                  {{ application.main_applicant?.last_name }}
                </div>
                <div class="max-w-[160px] truncate">
                  {{ application.main_applicant?.street }}
                </div>
                <div>
                  {{ application.main_applicant?.postal_code }} {{ application.main_applicant?.city }}
                </div>
              </TableCell>

              <TableCell>
                <StatusBadge
                  :statusKey="display(application).key"
                  :label="display(application).label"
                />
              </TableCell>

              <TableCell>
                {{ fmtDate(application.opened_at) }}
              </TableCell>
              <TableCell>
                {{ fmtDate(application.extended_at) }}
              </TableCell>
              <TableCell>
                {{ fmtDate(application.earliest_move_in) }}
              </TableCell>
              <TableCell class="text-right tabular-nums">
                {{ fmtMoney(application.max_gross_rent) }}
              </TableCell>
              <TableCell>
                {{ application.total_persons }}
              </TableCell>
              <TableCell>
                {{ fmtList(application.rooms) }}
              </TableCell>
              <TableCell>
                {{ fmtList(application.districts) }}
              </TableCell>
              <TableCell variant="last">
                {{ application.main_applicant?.income_bracket ?? '–' }}
              </TableCell>
            </tr>
            <tr v-if="!store.applications.length">
              <td :colspan="columnCount" class="py-30 text-center text-sm text-light-gray">
                Keine Anmeldungen gefunden.
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </Panel>

  <div class="mt-25">
    <Pagination
      :page="store.page"
      :last-page="store.lastPage"
      :total="store.total"
      :from="store.from"
      :to="store.to"
      @change="goToPage"
    />
  </div>

  <BulkActionBar
    :count="selectedCount"
    :total="store.total"
    :can-select-all="canSelectAllMatching"
    :all-matching="selectAllMatching"
    @select-all="selectAll"
    @clear="clearSelection"
    @open="bulkOpen"
    @export="bulkExport"
    @delete="askBulkDelete"
  />

  <ConfirmDialog
    :open="confirmingDelete"
    :title="`${selectedCount} ${selectedCount === 1 ? 'Bewerbung' : 'Bewerbungen'} löschen`"
    :message="deleteMessage"
    confirmLabel="Löschen bestätigen"
    cancelLabel="Abbrechen"
    :destructive="true"
    @confirm="handleBulkDelete"
    @cancel="confirmingDelete = false"
  />
</template>
