<script setup>
import { computed } from 'vue'
import { useLookupsStore } from '@/stores/lookups'
import { fmtDate } from '@/utils/format'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import StatusBadge from '@/components/ui/badges/Status.vue'
import Select from '@/components/ui/form/Select.vue'
import Input from '@/components/ui/form/Input.vue'

// "Info" panel: the application's status. Saved through its own endpoint (an
// audit event) via the injected onSave, separate from the generic section update.
// Switching to Verlängert / Archiviert reveals a date field, pre-filled with the
// existing date or today.
const props = defineProps({
	application: { type: Object, required: true },
	onSave: { type: Function, required: true }, // async (draft) => Promise
})

const lookups = useLookupsStore()

const today = () => new Date().toISOString().slice(0, 10)

const source = computed(() => ({
	status: props.application.status.value,
	extended_at: props.application.extended_at?.slice(0, 10) ?? today(),
	archived_at: props.application.archived_at?.slice(0, 10) ?? today(),
}))

// Show only the timestamp belonging to the current state, never all of them.
const statusTimestamp = computed(() => {
	const a = props.application
	const byStatus = {
		opened: { label: 'Angemeldet', value: a.opened_at },
		extended: { label: 'Verlängert am', value: a.extended_at },
		archived: { label: 'Archiviert am', value: a.archived_at },
	}
	return byStatus[a.status.value] ?? null
})

// flagged ("Wichtig") overrides open/extended; archived is terminal — mirrors the list view.
const statusDisplay = computed(() => {
	const { status, flagged } = props.application
	if (status.value !== 'archived' && flagged) return { key: 'flagged', label: 'Wichtig' }
	return { key: status.value, label: status.label }
})
</script>

<template>
	<EditablePanel title="Info" :source="source" :onSave="onSave">
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

		<template #edit="{ draft, errors }">
			<InfoList>
				<EditRow label="Status" :error="errors.status">
					<Select v-model="draft.status" :options="lookups.options('statuses')" :placeholder="null" :hasError="!!errors.status" />
				</EditRow>
				<InfoRow v-if="draft.status === 'opened'" label="Angemeldet">
					{{ fmtDate(application.opened_at) }}
				</InfoRow>
				<EditRow v-if="draft.status === 'extended'" label="Verlängert am" :error="errors.extended_at">
					<Input v-model="draft.extended_at" type="date" :hasError="!!errors.extended_at" />
				</EditRow>
				<EditRow v-if="draft.status === 'archived'" label="Archiviert am" :error="errors.archived_at">
					<Input v-model="draft.archived_at" type="date" :hasError="!!errors.archived_at" />
				</EditRow>
			</InfoList>
		</template>
	</EditablePanel>
</template>
