<script setup>
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useApplicationsStore } from '@/stores/applications'
import { useBrowseStore } from '@/stores/browse'
import { useListQuery } from '@/composables/useListQuery'
import { useListSelection } from '@/composables/useListSelection'
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
const browse = useBrowseStore()
const toast = useToast()

const { sort, direction, search, goToPage, toggleSort, reload } = useListQuery({
	fetch: store.fetch,
	perPage: 15,
})

const pageIds = computed(() => store.applications.map((a) => a.id))
const total = computed(() => store.total)

const {
	filterActive,
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
} = useListSelection({ total, pageIds })

// Data columns, plus the selection checkbox column when the filter is active.
const columnCount = computed(() => (filterActive.value ? 12 : 11))

const confirmingDelete = ref(false)
const deleting = ref(false)

const askBulkDelete = () => { confirmingDelete.value = true }

async function handleBulkDelete() {
	deleting.value = true
	try {
		const { data } = await api.bulkDestroy(selectionPayload())
		confirmingDelete.value = false
		clearSelection()
		reload()
		toast.success(`${data.deleted} ${data.deleted === 1 ? 'Bewerbung' : 'Bewerbungen'} gelöscht.`)
	} catch {
		// errors surface as a toast via the axios interceptor
		confirmingDelete.value = false
	} finally {
		deleting.value = false
	}
}

// Restore is non-destructive, so no confirm dialog (mirrors single restore).
const trashedView = computed(() => route.query.status === 'deleted')

async function handleBulkRestore() {
	try {
		const { data } = await api.bulkRestore(selectionPayload())
		clearSelection()
		reload()
		toast.success(`${data.restored} ${data.restored === 1 ? 'Bewerbung' : 'Bewerbungen'} wiederhergestellt.`)
	} catch {
		// errors surface as a toast via the axios interceptor
	}
}

// Resolve the selection to its ordered ids, seed the browse store, and open the
// first one; the detail view then steps through exactly this set.
async function bulkOpen() {
	const payload = { ...selectionPayload(), sort: sort.value, direction: direction.value }
	const { data } = await api.bulkResolve(payload)
	if (!data.ids.length) return

	browse.start(data.ids)
	router.push({
		name: 'applications.show',
		params: { id: data.ids[0] },
		state: { from: route.fullPath },
	})
}

// Synchronous PDF export: POST the selection, stream the PDF back, trigger a
// browser download. The endpoint caps the selection; an over-cap or empty
// selection comes back as a 422 with a JSON message in the blob body.
const exporting = ref(false)

async function bulkExport() {
	if (exporting.value) return
	exporting.value = true
	try {
		const payload = { ...selectionPayload(), sort: sort.value, direction: direction.value }
		const response = await api.bulkExport(payload)
		triggerDownload(response)
	} catch (error) {
		toast.error((await readBlobError(error)) ?? 'Der Export konnte nicht erstellt werden.')
	} finally {
		exporting.value = false
	}
}

// Turn a blob response into a download, using the filename from Content-Disposition.
function triggerDownload(response) {
	const match = /filename="?([^"]+)"?/.exec(response.headers['content-disposition'] ?? '')
	const url = URL.createObjectURL(response.data)
	const link = document.createElement('a')
	link.href = url
	link.download = match?.[1] ?? 'export.pdf'
	document.body.appendChild(link)
	link.click()
	link.remove()
	URL.revokeObjectURL(url)
}

// Error bodies for a blob request arrive as a Blob; read the JSON message out.
async function readBlobError(error) {
	const data = error.response?.data
	if (!(data instanceof Blob)) return null
	try {
		return JSON.parse(await data.text())?.message ?? null
	} catch {
		return null
	}
}

const styles = {
	opened: { text: 'text-green' },
	extended: { text: 'text-violet' },
	archived: { text: 'text-gray' },
	knif: { text: 'text-red' },
}

function display(application) {
	const { value: key, label } = application.status
	return { key, label, ...styles[key] }
}

// Carry the current list URL into history state so the detail view's back link
// returns to the exact same filtered list. A single row is not a browse session.
function open(application) {
	browse.clear()
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
    :trashed="trashedView"
    :exporting="exporting"
    @select-all="selectAll"
    @clear="clearSelection"
    @open="bulkOpen"
    @export="bulkExport"
    @delete="askBulkDelete"
    @restore="handleBulkRestore"
  />

  <ConfirmDialog
    :open="confirmingDelete"
    title="Bewerbungen löschen"
    confirmLabel="Löschen bestätigen"
    cancelLabel="Abbrechen"
    :destructive="true"
    @confirm="handleBulkDelete"
    @cancel="confirmingDelete = false"
  >
    <strong>{{ selectedCount }} {{ selectedCount === 1 ? 'Bewerbung' : 'Bewerbungen' }}</strong>
    {{ selectedCount === 1 ? 'wird' : 'werden' }} aus der Liste entfernt.
    Sie {{ selectedCount === 1 ? 'bleibt' : 'bleiben' }} gespeichert und
    {{ selectedCount === 1 ? 'kann' : 'können' }} später wiederhergestellt werden.
  </ConfirmDialog>
</template>
