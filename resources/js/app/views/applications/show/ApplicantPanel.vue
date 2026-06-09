<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtDate } from '@/utils/format'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
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
			<div class="space-y-15">
				<Group>
					<Label :error="errors.salutation">Anrede</Label>
					<Select v-model="draft.salutation" :options="lookups.options('salutations')" :hasError="!!errors.salutation" />
				</Group>

				<div class="grid grid-cols-2 gap-15">
					<Group>
						<Label :error="errors.first_name">Vorname</Label>
						<Input v-model="draft.first_name" :hasError="!!errors.first_name" />
					</Group>
					<Group>
						<Label :error="errors.last_name">Name</Label>
						<Input v-model="draft.last_name" :hasError="!!errors.last_name" />
					</Group>
				</div>

				<Group v-if="!isMain">
					<Label :error="errors.relationship_to_main">Beziehung zum Hauptmieter</Label>
					<Select v-model="draft.relationship_to_main" :options="lookups.options('relationships')" :hasError="!!errors.relationship_to_main" />
				</Group>

				<Group v-if="!isMain">
					<Checkbox v-model="draft.same_address_as_main">Gleiche Adresse wie Hauptmieter</Checkbox>
				</Group>

				<template v-if="isMain || !draft.same_address_as_main">
					<div class="grid grid-cols-[1fr_auto] gap-15">
						<Group>
							<Label :error="errors.street">Strasse</Label>
							<Input v-model="draft.street" :hasError="!!errors.street" />
						</Group>
						<Group>
							<Label :error="errors.street_number">Nr.</Label>
							<Input v-model="draft.street_number" :hasError="!!errors.street_number" />
						</Group>
					</div>
					<div class="grid grid-cols-[auto_1fr] gap-15">
						<Group>
							<Label :error="errors.postal_code">PLZ</Label>
							<Input v-model="draft.postal_code" :hasError="!!errors.postal_code" />
						</Group>
						<Group>
							<Label :error="errors.city">Ort</Label>
							<Input v-model="draft.city" :hasError="!!errors.city" />
						</Group>
					</div>
				</template>

				<Group>
					<Label :error="errors.birth_date">Geburtsdatum</Label>
					<Input v-model="draft.birth_date" type="date" :hasError="!!errors.birth_date" />
				</Group>

				<Group>
					<Label :error="errors.marital_status">Zivilstand</Label>
					<Select v-model="draft.marital_status" :options="lookups.options('marital_statuses')" :hasError="!!errors.marital_status" />
				</Group>

				<Group>
					<Label :error="errors.nationality">Nationalität</Label>
					<Select v-model="draft.nationality" :options="lookups.options('nationalities')" :hasError="!!errors.nationality" />
				</Group>

				<Group v-if="draft.nationality === 'CH'">
					<Label :error="errors.place_of_origin">Heimatort</Label>
					<Input v-model="draft.place_of_origin" :hasError="!!errors.place_of_origin" />
				</Group>

				<template v-else-if="draft.nationality">
					<Group>
						<Label :error="errors.residence_permit">Aufenthaltsbewilligung</Label>
						<Select v-model="draft.residence_permit" :options="lookups.options('residence_permits')" :hasError="!!errors.residence_permit" />
					</Group>
					<Group>
						<Label :error="errors.swiss_residence_since">In der Schweiz seit</Label>
						<Input v-model="draft.swiss_residence_since" type="date" :hasError="!!errors.swiss_residence_since" />
					</Group>
				</template>

				<div class="grid grid-cols-2 gap-15">
					<Group>
						<Label :error="errors.mobile_phone">Telefon (mobil)</Label>
						<Input v-model="draft.mobile_phone" :hasError="!!errors.mobile_phone" />
					</Group>
					<Group>
						<Label :error="errors.landline_phone">Telefon (Festnetz)</Label>
						<Input v-model="draft.landline_phone" :hasError="!!errors.landline_phone" />
					</Group>
				</div>

				<Group>
					<Label :error="errors.email">E-Mail</Label>
					<Input v-model="draft.email" type="email" :hasError="!!errors.email" />
				</Group>

				<Group>
					<Label :error="errors.occupation">Beruf</Label>
					<Input v-model="draft.occupation" :hasError="!!errors.occupation" />
				</Group>

				<Group>
					<Label :error="errors.employment_status">Erwerbssituation</Label>
					<Select v-model="draft.employment_status" :options="lookups.options('employment_statuses')" :hasError="!!errors.employment_status" />
				</Group>

				<Group>
					<Checkbox v-model="draft.debt_enforcement_last_2y">Betreibungen in den letzten 2 Jahren</Checkbox>
				</Group>
			</div>
		</template>
	</EditablePanel>
</template>
