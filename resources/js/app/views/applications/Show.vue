<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '@/api/applications'
import { useLookupsStore } from '@/stores/lookups'
import { useToast } from '@/composables/useToast'
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

onMounted(async () => {
	try {
		const [, { data }] = await Promise.all([lookups.fetch(), api.show(props.id)])
		app.value = data.data
	} finally {
		loading.value = false
	}
})

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

// Return to the originating filtered list (search / page / sort) when we have it
// in history state; fall back to the bare list for deep links / refreshes.
const backTo = window.history.state?.from || { name: 'applications.index' }

const title = computed(() => {
	if (!app.value) return ''
	const a = app.value.main_applicant
	const name = a ? `${lookups.label('salutations', a.salutation)} ${a.first_name} ${a.last_name}` : ''
	return `${app.value.reference_number} – ${name}`.trim()
})
</script>

<template>
	<div v-if="loading" class="text-sm text-light-gray">
		Laden …
	</div>

	<div v-else-if="app">
		<header class="flex items-center justify-between mb-30">
			<Heading1>{{ title }}</Heading1>
			<RouterLink :to="backTo">
				<Button variant="ghost" size="sm">← Zurück zur Liste</Button>
			</RouterLink>
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

				<RestorePanel v-if="isTrashed" :onRestore="handleRestore" />
				<DeletePanel v-else :onDelete="askDelete" />
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
	</div>
</template>
