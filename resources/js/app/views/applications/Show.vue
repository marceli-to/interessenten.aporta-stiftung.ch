<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import api from '@/api/applications'
import { useLookupsStore } from '@/stores/lookups'
import { useToast } from '@/composables/useToast'
import { fmtDate } from '@/utils/format'
import Heading1 from '@/components/ui/headings/H1.vue'
import Panel from '@/components/ui/panels/Display.vue'
import StatusBadge from '@/components/ui/badges/Status.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import Select from '@/components/ui/form/Select.vue'
import Input from '@/components/ui/form/Input.vue'
import ApplicantPanel from '@/views/applications/show/ApplicantPanel.vue'
import EmployerPanel from '@/views/applications/show/EmployerPanel.vue'
import HousingPanel from '@/views/applications/show/HousingPanel.vue'
import HousingWishPanel from '@/views/applications/show/HousingWishPanel.vue'
import HouseholdPanel from '@/views/applications/show/HouseholdPanel.vue'

const props = defineProps({
	id: { type: String, required: true },
})

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

// Info panel edits the status; switching to Verlängert / Archiviert reveals a
// date field, pre-filled with the existing date or today.
const today = () => new Date().toISOString().slice(0, 10)
const infoSource = computed(() =>
	app.value
		? {
			status: app.value.status.value,
			extended_at: app.value.extended_at?.slice(0, 10) ?? today(),
			archived_at: app.value.archived_at?.slice(0, 10) ?? today(),
		}
		: null
)

const title = computed(() => {
	if (!app.value) return ''
	const a = app.value.main_applicant
	const name = a ? `${lookups.label('salutations', a.salutation)} ${a.first_name} ${a.last_name}` : ''
	return `Nr. ${app.value.reference_number} – ${name}`.trim()
})

// Show only the timestamp belonging to the current state, never all of them.
const statusTimestamp = computed(() => {
	if (!app.value) return null
	const byStatus = {
		opened: { label: 'Angemeldet', value: app.value.opened_at },
		extended: { label: 'Verlängert am', value: app.value.extended_at },
		archived: { label: 'Archiviert am', value: app.value.archived_at },
	}
	return byStatus[app.value.status.value] ?? null
})

// flagged ("Wichtig") overrides open/extended; archived is terminal — mirrors the list view.
const statusDisplay = computed(() => {
	if (!app.value) return { key: 'opened', label: '' }
	const { status, flagged } = app.value
	if (status.value !== 'archived' && flagged) return { key: 'flagged', label: 'Wichtig' }
	return { key: status.value, label: status.label }
})
</script>

<template>
	<div v-if="loading" class="text-sm text-light-gray">
		Laden …
	</div>

	<div v-else-if="app">
		<header class="flex items-center justify-between mb-40">
			<Heading1>{{ title }}</Heading1>
			<RouterLink :to="{ name: 'applications.index' }" class="text-sm text-gray hover:text-blue">
				← Zurück zur Liste
			</RouterLink>
		</header>

		<div class="grid grid-cols-12 gap-30">
			<div class="col-span-8 flex flex-col gap-30">
				<EditablePanel title="Info" :source="infoSource" :onSave="saveStatus">
					<template #view>
						<InfoList>
							<InfoRow label="Status">
								<StatusBadge :statusKey="statusDisplay.key" :label="statusDisplay.label" />
							</InfoRow>
							<InfoRow v-if="statusTimestamp" :label="statusTimestamp.label">
								{{ fmtDate(statusTimestamp.value) }}
							</InfoRow>
						</InfoList>
					</template>

					<template #edit="{ draft }">
						<InfoList>
							<InfoRow label="Status">
								<Select v-model="draft.status" :options="lookups.options('statuses')" :placeholder="null" />
							</InfoRow>
							<InfoRow v-if="draft.status === 'opened'" label="Angemeldet">
								{{ fmtDate(app.opened_at) }}
							</InfoRow>
							<InfoRow v-if="draft.status === 'extended'" label="Verlängert am">
								<Input v-model="draft.extended_at" type="date" />
							</InfoRow>
							<InfoRow v-if="draft.status === 'archived'" label="Archiviert am">
								<Input v-model="draft.archived_at" type="date" />
							</InfoRow>
						</InfoList>
					</template>
				</EditablePanel>

				<ApplicantPanel
					title="Hauptmieter"
					:applicant="app.main_applicant"
					section="main_applicant"
					:isMain="true"
					:onSave="saveMainApplicant"
				/>

				<EmployerPanel
					v-if="app.main_applicant?.employer"
					:applicant="app.main_applicant"
					section="main_applicant"
					:onSave="saveMainApplicant"
				/>

				<HousingPanel
					:applicant="app.main_applicant"
					section="main_applicant"
					:onSave="saveMainApplicant"
				/>

				<!-- Partner: rendered only when a co-applicant exists. -->
				<template v-if="app.co_applicant">
					<ApplicantPanel
						title="Partner*in"
						:applicant="app.co_applicant"
						section="co_applicant"
						:isMain="false"
						:onSave="saveCoApplicant"
					/>
					<EmployerPanel
						v-if="app.co_applicant.employer"
						title="Arbeitgeber Partner*in"
						:applicant="app.co_applicant"
						section="co_applicant"
						:onSave="saveCoApplicant"
					/>
					<HousingPanel
						title="Wohnsituation Partner*in"
						:applicant="app.co_applicant"
						section="co_applicant"
						:onSave="saveCoApplicant"
					/>
				</template>

				<HousingWishPanel :source="app.housing_wish" :onSave="saveHousingWish" />

				<HouseholdPanel :source="householdSource" :onSave="saveHousehold" />
			</div>

			<div class="col-span-4">
				<Panel variant="highlight" />
			</div>
		</div>
	</div>
</template>
