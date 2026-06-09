<script setup>
import { useLookupsStore } from '@/stores/lookups'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import Textarea from '@/components/ui/form/Textarea.vue'
import Checkbox from '@/components/ui/form/Checkbox.vue'

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

const yesNo = (v) => (v == null ? '–' : v ? 'Ja' : 'Nein')

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
					{{ yesNo(data.current_housing?.terminated_by_landlord) }}
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
			<div v-if="draft.current_housing" class="space-y-15">
				<Group>
					<Label :error="errors.tenant_role">Rolle</Label>
					<Select v-model="draft.current_housing.tenant_role" :options="lookups.options('tenant_roles')" :hasError="!!errors.tenant_role" />
				</Group>
				<Group>
					<Checkbox v-model="draft.current_housing.terminated_by_landlord">Gekündigt durch Vermieter</Checkbox>
				</Group>
				<Group v-if="draft.current_housing.terminated_by_landlord">
					<Label :error="errors.termination_reason">Kündigungsgrund</Label>
					<Textarea v-model="draft.current_housing.termination_reason" :hasError="!!errors.termination_reason" />
				</Group>
				<Group>
					<Label :error="errors.landlord_name">Vermieter</Label>
					<Input v-model="draft.current_housing.landlord_name" :hasError="!!errors.landlord_name" />
				</Group>
				<div class="grid grid-cols-2 gap-15">
					<Group>
						<Label :error="errors.landlord_contact_person">Kontaktperson</Label>
						<Input v-model="draft.current_housing.landlord_contact_person" :hasError="!!errors.landlord_contact_person" />
					</Group>
					<Group>
						<Label :error="errors.landlord_phone">Telefon Vermieter</Label>
						<Input v-model="draft.current_housing.landlord_phone" :hasError="!!errors.landlord_phone" />
					</Group>
				</div>
				<Group>
					<Label :error="errors.rent_duration">Wohnhaft seit</Label>
					<Select v-model="draft.current_housing.rent_duration" :options="lookups.options('rent_durations')" :hasError="!!errors.rent_duration" />
				</Group>
				<Group>
					<Label :error="errors.previous_landlord">Frühere*r Vermieter*in</Label>
					<Input v-model="draft.current_housing.previous_landlord" :hasError="!!errors.previous_landlord" />
				</Group>
			</div>
		</template>
	</EditablePanel>
</template>
