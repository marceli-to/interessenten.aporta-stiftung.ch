<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtDate } from '@/utils/format'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import Checkbox from '@/components/ui/form/Checkbox.vue'

// Personal data for one applicant. `source` is the WHOLE applicant object
// (incl. employer + current_housing) because the backend replaces the applicant
// wholesale on save — the sibling Employer/Housing panels edit other slices of
// the same object. Reused for both main_applicant and co_applicant.
const props = defineProps({
	title: { type: String, required: true },
	applicant: { type: Object, required: true },
	section: { type: String, required: true }, // 'main_applicant' | 'co_applicant'
	isMain: { type: Boolean, default: true },
	onSave: { type: Function, required: true }, // async (fullApplicantDraft) => Promise
})

const lookups = useLookupsStore()

const yesNo = (v) => (v == null ? '–' : v ? 'Ja' : 'Nein')

function fullName(a) {
	return [a.first_name, a.last_name].filter(Boolean).join(' ') || '–'
}

function address(a) {
	const line = [a.street, a.street_number].filter(Boolean).join(' ')
	const place = [a.postal_code, a.city].filter(Boolean).join(' ')
	return [line, place].filter(Boolean).join(', ') || '–'
}

function nationality(a) {
	if (!a.nationality) return '–'
	const parts = [lookups.label('nationalities', a.nationality)]
	if (a.nationality === 'CH' && a.place_of_origin) {
		parts.push(`Heimatort ${a.place_of_origin}`)
	} else if (a.residence_permit) {
		parts.push(`Ausweis ${lookups.label('residence_permits', a.residence_permit)}`)
	}
	return parts.join(' · ')
}
</script>

<template>
	<EditablePanel :title="title" :source="applicant" :errorPrefix="section" :onSave="onSave">
		<template #view="{ data }">
			<InfoList>
				<InfoRow label="Anrede">
					{{ lookups.label('salutations', data.salutation) }}
				</InfoRow>
				<InfoRow label="Name">
					{{ fullName(data) }}
				</InfoRow>
				<InfoRow v-if="!isMain" label="Beziehung">
					{{ lookups.label('relationships', data.relationship_to_main) }}
				</InfoRow>
				<InfoRow label="Adresse">
					{{ data.same_address_as_main && !isMain ? 'Wie Hauptmieter' : address(data) }}
				</InfoRow>
				<InfoRow label="Geburtsdatum">
					{{ fmtDate(data.birth_date) }}
				</InfoRow>
				<InfoRow label="Zivilstand">
					{{ lookups.label('marital_statuses', data.marital_status) }}
				</InfoRow>
				<InfoRow label="Nationalität">
					{{ nationality(data) }}
				</InfoRow>
				<InfoRow label="Telefon">
					{{ data.mobile_phone || '–' }}
				</InfoRow>
				<InfoRow label="E-Mail">
					{{ data.email || '–' }}
				</InfoRow>
				<InfoRow label="Beruf">
					{{ data.occupation || '–' }}
				</InfoRow>
				<InfoRow label="Erwerbssituation">
					{{ lookups.label('employment_statuses', data.employment_status) }}
				</InfoRow>
				<InfoRow label="Betreibungen">
					{{ yesNo(data.debt_enforcement_last_2y) }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<InfoList>
				<EditRow label="Anrede" :error="errors.salutation">
					<Select v-model="draft.salutation" :options="lookups.options('salutations')" :hasError="!!errors.salutation" />
				</EditRow>
				<EditRow label="Vorname" :error="errors.first_name">
					<Input v-model="draft.first_name" :hasError="!!errors.first_name" />
				</EditRow>
				<EditRow label="Name" :error="errors.last_name">
					<Input v-model="draft.last_name" :hasError="!!errors.last_name" />
				</EditRow>

				<EditRow v-if="!isMain" label="Beziehung zum Hauptmieter" :error="errors.relationship_to_main">
					<Select v-model="draft.relationship_to_main" :options="lookups.options('relationships')" :hasError="!!errors.relationship_to_main" />
				</EditRow>
				<EditRow v-if="!isMain" label="Gleiche Adresse wie Hauptmieter">
					<Checkbox v-model="draft.same_address_as_main" />
				</EditRow>

				<template v-if="isMain || !draft.same_address_as_main">
					<EditRow label="Strasse" :error="errors.street">
						<Input v-model="draft.street" :hasError="!!errors.street" />
					</EditRow>
					<EditRow label="Nr." :error="errors.street_number">
						<Input v-model="draft.street_number" :hasError="!!errors.street_number" />
					</EditRow>
					<EditRow label="PLZ" :error="errors.postal_code">
						<Input v-model="draft.postal_code" :hasError="!!errors.postal_code" />
					</EditRow>
					<EditRow label="Ort" :error="errors.city">
						<Input v-model="draft.city" :hasError="!!errors.city" />
					</EditRow>
				</template>

				<EditRow label="Geburtsdatum" :error="errors.birth_date">
					<Input v-model="draft.birth_date" type="date" :hasError="!!errors.birth_date" />
				</EditRow>
				<EditRow label="Zivilstand" :error="errors.marital_status">
					<Select v-model="draft.marital_status" :options="lookups.options('marital_statuses')" :hasError="!!errors.marital_status" />
				</EditRow>
				<EditRow label="Nationalität" :error="errors.nationality">
					<Select v-model="draft.nationality" :options="lookups.options('nationalities')" :hasError="!!errors.nationality" />
				</EditRow>

				<EditRow v-if="draft.nationality === 'CH'" label="Heimatort" :error="errors.place_of_origin">
					<Input v-model="draft.place_of_origin" :hasError="!!errors.place_of_origin" />
				</EditRow>
				<template v-else-if="draft.nationality">
					<EditRow label="Aufenthaltsbewilligung" :error="errors.residence_permit">
						<Select v-model="draft.residence_permit" :options="lookups.options('residence_permits')" :hasError="!!errors.residence_permit" />
					</EditRow>
					<EditRow label="In der Schweiz seit" :error="errors.swiss_residence_since">
						<Input v-model="draft.swiss_residence_since" type="date" :hasError="!!errors.swiss_residence_since" />
					</EditRow>
				</template>

				<EditRow label="Telefon (mobil)" :error="errors.mobile_phone">
					<Input v-model="draft.mobile_phone" :hasError="!!errors.mobile_phone" />
				</EditRow>
				<EditRow label="Telefon (Festnetz)" :error="errors.landline_phone">
					<Input v-model="draft.landline_phone" :hasError="!!errors.landline_phone" />
				</EditRow>
				<EditRow label="E-Mail" :error="errors.email">
					<Input v-model="draft.email" type="email" :hasError="!!errors.email" />
				</EditRow>
				<EditRow label="Beruf" :error="errors.occupation">
					<Input v-model="draft.occupation" :hasError="!!errors.occupation" />
				</EditRow>
				<EditRow label="Erwerbssituation" :error="errors.employment_status">
					<Select v-model="draft.employment_status" :options="lookups.options('employment_statuses')" :hasError="!!errors.employment_status" />
				</EditRow>
				<EditRow label="Betreibungen">
					<Checkbox v-model="draft.debt_enforcement_last_2y" />
				</EditRow>
			</InfoList>
		</template>
	</EditablePanel>
</template>
