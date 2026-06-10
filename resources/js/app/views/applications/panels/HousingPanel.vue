<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtYesNo } from '@/utils/format'
import { yesNoOptions } from '@/utils/options'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import Textarea from '@/components/ui/form/Textarea.vue'

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
	return [ch?.landlord_name, ch?.landlord_contact_person, ch?.landlord_phone]
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
				<InfoRow label="Wohnhaft seit">
					{{ lookups.label('rent_durations', data.current_housing?.rent_duration) }}
				</InfoRow>
				<InfoRow label="Frühere*r Vermieter*in">
					{{ data.current_housing?.previous_landlord || '–' }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<InfoList v-if="draft.current_housing">
				<EditRow label="Rolle" :error="errors.tenant_role">
					<Select v-model="draft.current_housing.tenant_role" :options="lookups.options('tenant_roles')" :hasError="!!errors.tenant_role" />
				</EditRow>
				<EditRow label="Gekündigt durch Vermieter">
					<Select v-model="draft.current_housing.terminated_by_landlord" :options="yesNoOptions" />
				</EditRow>
				<EditRow v-if="draft.current_housing.terminated_by_landlord" label="Kündigungsgrund" :error="errors.termination_reason">
					<Textarea v-model="draft.current_housing.termination_reason" :hasError="!!errors.termination_reason" />
				</EditRow>
				<EditRow label="Vermieter" :error="errors.landlord_name">
					<Input v-model="draft.current_housing.landlord_name" :hasError="!!errors.landlord_name" />
				</EditRow>
				<EditRow label="Kontaktperson" :error="errors.landlord_contact_person">
					<Input v-model="draft.current_housing.landlord_contact_person" :hasError="!!errors.landlord_contact_person" />
				</EditRow>
				<EditRow label="Telefon Vermieter" :error="errors.landlord_phone">
					<Input v-model="draft.current_housing.landlord_phone" :hasError="!!errors.landlord_phone" />
				</EditRow>
				<EditRow label="Wohnhaft seit" :error="errors.rent_duration">
					<Select v-model="draft.current_housing.rent_duration" :options="lookups.options('rent_durations')" :hasError="!!errors.rent_duration" />
				</EditRow>
				<EditRow label="Frühere*r Vermieter*in" :error="errors.previous_landlord">
					<Input v-model="draft.current_housing.previous_landlord" :hasError="!!errors.previous_landlord" />
				</EditRow>
			</InfoList>
		</template>
	</EditablePanel>
</template>
