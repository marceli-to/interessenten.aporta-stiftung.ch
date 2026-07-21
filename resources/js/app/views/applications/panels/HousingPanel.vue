<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtYesNo, fmtPhone } from '@/utils/format'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import HousingFields from '@/views/applications/panels/fields/HousingFields.vue'

// Current-housing slice of an applicant. Same `source` (whole applicant) and
// onSave as ApplicantPanel; edits draft.current_housing. current_housing is a
// required block in the backend payload, so it always exists.
const props = defineProps({
	title: { type: String, default: 'Aktuelle Wohnsituation' },
	applicant: { type: Object, required: true },
	section: { type: String, required: true },
	onSave: { type: Function, required: true },
})

const lookups = useLookupsStore()

function landlord(ch) {
	const phone = ch?.landlord_phone ? fmtPhone(ch.landlord_phone) : null
	return [ch?.landlord_name, ch?.landlord_contact_person, phone]
		.filter(Boolean)
		.join(' · ') || '–'
}
</script>

<template>
	<EditablePanel
		:title="title"
		:source="applicant"
		:errorPrefix="`${section}.current_housing`"
		:onSave="onSave"
	>
		<template #view="{ data }">
			<InfoList>
				<InfoRow label="Rolle">
					{{ lookups.label('tenant_roles', data.current_housing?.tenant_role) }}
				</InfoRow>
				<InfoRow label="Gekündigt durch Vermieter">
					{{ fmtYesNo(data.current_housing?.terminated_by_landlord) }}
				</InfoRow>
				<InfoRow v-if="data.current_housing?.terminated_by_landlord" label="Kündigungsgrund">
					{{ data.current_housing?.termination_reason || '–' }}
				</InfoRow>
				<InfoRow label="Aktueller Vermieter">
					{{ landlord(data.current_housing) }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<HousingFields :draft="draft" :errors="errors" />
		</template>
	</EditablePanel>
</template>
