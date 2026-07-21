<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtDate, fmtYesNo, fmtPhone } from '@/utils/format'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import Button from '@/components/ui/form/Button.vue'
import ApplicantFields from '@/views/applications/panels/fields/ApplicantFields.vue'

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
	// Only passed for the co-applicant: opens the "Partner*in entfernen" confirm
	// dialog owned by Show.vue. The main applicant can't be removed.
	onRemove: { type: Function, default: null },
})

const lookups = useLookupsStore()

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
		<template v-if="onRemove" #action>
			<Button variant="danger" size="sm" icon="trash" @click="onRemove">
				Entfernen
			</Button>
		</template>

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
				<InfoRow label="Telefon (mobil)">
					{{ fmtPhone(data.mobile_phone) }}
				</InfoRow>
				<InfoRow v-if="data.landline_phone" label="Telefon (Festnetz)">
					{{ fmtPhone(data.landline_phone) }}
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
					{{ fmtYesNo(data.debt_enforcement_last_2y) }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<ApplicantFields :draft="draft" :errors="errors" :isMain="isMain" />
		</template>
	</EditablePanel>
</template>
