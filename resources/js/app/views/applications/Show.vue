<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '@/api/applications'
import { useLookupsStore } from '@/stores/lookups'
import { useToast } from '@/composables/useToast'
import Browse from '@/views/applications/Browse.vue'
import Heading1 from '@/components/ui/headings/H1.vue'
import Button from '@/components/ui/form/Button.vue'
import StatusPanel from '@/views/applications/panels/StatusPanel.vue'
import ApplicantPanel from '@/views/applications/panels/ApplicantPanel.vue'
import EmployerPanel from '@/views/applications/panels/EmployerPanel.vue'
import HousingPanel from '@/views/applications/panels/HousingPanel.vue'
import HousingWishPanel from '@/views/applications/panels/HousingWishPanel.vue'
import HouseholdPanel from '@/views/applications/panels/HouseholdPanel.vue'
import NotesPanel from '@/views/applications/panels/NotesPanel.vue'
import HistoryPanel from '@/views/applications/panels/HistoryPanel.vue'
import DeletePanel from '@/views/applications/panels/DeletePanel.vue'
import RestorePanel from '@/views/applications/panels/RestorePanel.vue'
import ConfirmDialog from '@/components/ui/dialog/ConfirmDialog.vue'

const props = defineProps({
	id: { type: String, required: true },
})

const router = useRouter()
const lookups = useLookupsStore()
const toast = useToast()

const app = ref(null)
const loading = ref(true)

async function load(id) {
	loading.value = true
	try {
		const [, { data }] = await Promise.all([lookups.fetch(), api.show(id)])
		app.value = data.data
	} finally {
		loading.value = false
	}
}

onMounted(() => load(props.id))

// Browsing a selection re-uses this component (only the :id param changes), so a
// route change won't re-trigger onMounted — reload explicitly when the id moves.
watch(() => props.id, (id) => load(id))

// One canonical object, one save path. A panel hands us the section payload; we
// PUT it, then replace `app` with the server's fresh full representation so every
// panel re-renders from the source of truth. Errors (incl. 422) propagate to the
// calling EditablePanel, which keeps itself open and renders inline messages.
async function update(payload) {
	const { data } = await api.update(props.id, payload)
	app.value = data.data
	toast.success('Änderungen gespeichert.')
}

// Status has its own endpoint (writes an audit event), separate from `update`.
async function updateStatus(payload) {
	const { data } = await api.updateStatus(props.id, payload)
	app.value = data.data
	toast.success('Status aktualisiert.')
}

const saveStatus = (draft) => {
	const payload = { status: draft.status }
	if (draft.status === 'extended') payload.extended_at = draft.extended_at
	if (draft.status === 'archived') payload.archived_at = draft.archived_at
	return updateStatus(payload)
}

const saveMainApplicant = (draft) => update({ main_applicant: draft })
const saveCoApplicant = (draft) => update({ co_applicant: draft })
const saveHousingWish = (draft) => update({ housing_wish: draft })

// Notes are self-contained: the NotesPanel owns its list and talks to the notes
// endpoints itself (see NotesPanel.vue). Show.vue only hands it the initial list.

const saveHousehold = (draft) => {
	const adults = Number(draft.info.adults_count) || 0
	const children = Number(draft.info.children_count) || 0
	return update({
		household_info: { ...draft.info, total_persons: adults + children },
		children: draft.children,
	})
}

// Household panel needs both sections bundled into a single editable object.
const householdSource = computed(() =>
	app.value ? { info: app.value.household_info, children: app.value.children } : null
)

// Deletion is confirmed through a ConfirmDialog: the DeletePanel button opens it,
// confirming runs the destroy and (on success) redirects to the list.
const confirmingDelete = ref(false)

const askDelete = () => { confirmingDelete.value = true }

async function handleDelete() {
	try {
		await api.destroy(props.id)
		toast.success('Bewerbung gelöscht.')
		router.push({ name: 'applications.index' })
	} catch {
		// failure already surfaced as a toast by the axios interceptor
		confirmingDelete.value = false
	}
}

// Soft-deleted applications open read-only from the "Gelöscht" list; the sidebar
// shows the restore panel instead of the delete one (toggled on `deleted_at`).
const isTrashed = computed(() => !!app.value?.deleted_at)

// Restore is non-destructive, so no confirm dialog: one click, then back to the
// list (mirroring the delete flow's redirect).
async function handleRestore() {
	try {
		await api.restore(props.id)
		toast.success('Bewerbung wiederhergestellt.')
		router.push({ name: 'applications.index' })
	} catch {
		// failure already surfaced as a toast by the axios interceptor
	}
}

// Synchronous PDF export of this single application: POST its id, stream the PDF
// back and trigger a browser download (same contract as the list's bulk export).
const exporting = ref(false)

async function exportApplication() {
	if (exporting.value) return
	exporting.value = true
	try {
		const response = await api.bulkExport({ ids: [Number(props.id)] })
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

// Return to the originating filtered list (search / page / sort) when we have it
// in history state; fall back to the bare list for deep links / refreshes.
const backTo = window.history.state?.from || { name: 'applications.index' }

// --- Browse (Resultatansicht) ------------------------------------------------
// The Browse component (header) owns the prev/next UI + browse-store derivation;
// the view just handles navigation, carrying the same `from` state so the back
// link survives stepping through the set.
function goToBrowse(id) {
	router.push({
		name: 'applications.show',
		params: { id },
		state: { from: backTo },
	})
}

const title = computed(() => {
	if (!app.value) return ''
	const a = app.value.main_applicant
	const name = a ? `${lookups.label('salutations', a.salutation)} ${a.first_name} ${a.last_name}` : ''
	return `${app.value.reference_number} – ${name}`.trim()
})
</script>

<template>
	<template v-if="loading">
		<div class="text-sm text-light-gray">
			Laden …
		</div>
	</template>

	<template v-else-if="app">
		<header class="grid grid-cols-3 items-center mb-30">
			<Heading1 class="truncate">{{ title }}</Heading1>

			<Browse :id="id" @navigate="goToBrowse" />

			<div class="justify-self-end flex items-center gap-20">
				<RouterLink :to="backTo">
					<Button variant="ghost" size="sm">← Zurück zur Liste</Button>
				</RouterLink>
				<Button
					variant="primary"
					size="sm"
					icon="download-simple"
					:loading="exporting"
					@click="exportApplication"
				>
					Exportieren
				</Button>
			</div>
		</header>

		<div class="grid grid-cols-12 gap-30">
			<div class="col-span-8 flex flex-col gap-30">

				<StatusPanel 
          :application="app" 
          :onSave="saveStatus" />

				<ApplicantPanel
					title="Hauptmieter"
					:applicant="app.main_applicant"
					section="main_applicant"
					:isMain="true"
					:onSave="saveMainApplicant"	/>

				<EmployerPanel
					v-if="app.main_applicant?.employer"
					:applicant="app.main_applicant"
					section="main_applicant"
					:onSave="saveMainApplicant" />

				<HousingPanel
					:applicant="app.main_applicant"
					section="main_applicant"
					:onSave="saveMainApplicant"	/>

				<!-- Partner: rendered only when a co-applicant exists. -->
				<template v-if="app.co_applicant">

					<ApplicantPanel
						title="Partner*in"
						:applicant="app.co_applicant"
						section="co_applicant"
						:isMain="false"
						:onSave="saveCoApplicant"	/>

					<EmployerPanel
						v-if="app.co_applicant.employer"
						title="Arbeitgeber Partner*in"
						:applicant="app.co_applicant"
						section="co_applicant"
						:onSave="saveCoApplicant" />

					<HousingPanel
						title="Wohnsituation Partner*in"
						:applicant="app.co_applicant"
						section="co_applicant"
						:onSave="saveCoApplicant"	/>

				</template>

				<HousingWishPanel 
          :source="app.housing_wish" 
          :onSave="saveHousingWish" />

				<HouseholdPanel 
          :source="householdSource" 
          :onSave="saveHousehold" />
          
			</div>

			<div class="col-span-4 flex flex-col gap-30">
				<NotesPanel
					:application-id="app.id"
					:notes="app.notes" />

				<HistoryPanel :events="app.status_events" />

				<template v-if="isTrashed">
					<RestorePanel :onRestore="handleRestore" />
				</template>
				<template v-else>
					<DeletePanel :onDelete="askDelete" />
				</template>
			</div>
		</div>

		<ConfirmDialog
			:open="confirmingDelete"
			title="Bewerbung löschen"
			:message="`Die Bewerbung «${title}» wird aus der Liste entfernt. Sie bleibt gespeichert und kann später wiederhergestellt werden.`"
			confirmLabel="Löschen bestätigen"
			cancelLabel="Abbrechen"
			:destructive="true"
			@confirm="handleDelete"
			@cancel="confirmingDelete = false"
		/>
	</template>
</template>
