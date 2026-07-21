<script setup>
import { scopeErrors } from '@/utils/errors'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import Heading2 from '@/components/ui/headings/H2.vue'
import ApplicantFields from '@/views/applications/panels/fields/ApplicantFields.vue'
import EmployerFields from '@/views/applications/panels/fields/EmployerFields.vue'
import HousingFields from '@/views/applications/panels/fields/HousingFields.vue'

// Placeholder shown in the co-applicant's slot while the application has none.
// "Hinzufügen" opens ONE form covering all three co-applicant blocks (person,
// employer, current housing) because the backend only accepts the section as a
// whole — a half-filled co_applicant is rejected. Once saved, Show.vue renders
// the regular three panels instead and this one disappears.
const props = defineProps({
	onSave: { type: Function, required: true }, // async (coApplicantDraft) => Promise
})

// EditablePanel clones this on every "Hinzufügen", so an abandoned draft leaves
// nothing behind. employer/current_housing are scaffolded by their field groups.
const blank = {
	salutation: null,
	first_name: '',
	last_name: '',
	relationship_to_main: null,
	same_address_as_main: true,
	street: null,
	street_number: null,
	postal_code: null,
	city: null,
	birth_date: null,
	marital_status: null,
	nationality: null,
	place_of_origin: null,
	residence_permit: null,
	swiss_residence_since: null,
	mobile_phone: '',
	landline_phone: null,
	email: '',
	occupation: '',
	employment_status: null,
	debt_enforcement_last_2y: false,
	employer: null,
	current_housing: null,
}

// The employer block only applies to employees. Drop a scaffold left behind by
// switching the employment status back and forth — the backend would otherwise
// reject its empty fields (employer.* is required_with:employer).
function save(draft) {
	return props.onSave({
		...draft,
		employer: draft.employment_status === 'employed' ? draft.employer : null,
	})
}
</script>

<template>
	<EditablePanel
		title="Partner*in"
		:source="blank"
		errorPrefix="co_applicant"
		editLabel="Partner*in hinzufügen"
		editIcon="plus"
		:onSave="save"
	>
		<template #view>
			<p class="text-sm text-light-gray">
				Für diese Bewerbung ist keine zweite Person erfasst.
			</p>
		</template>

		<template #edit="{ draft, errors }">
			<ApplicantFields :draft="draft" :errors="errors" :isMain="false" />

			<template v-if="draft.employment_status === 'employed'">
				<Heading2 class="mt-30 mb-15">Aktueller Arbeitgeber</Heading2>
				<EmployerFields :draft="draft" :errors="scopeErrors(errors, 'employer')" />
			</template>

			<Heading2 class="mt-30 mb-15">Aktuelle Wohnsituation</Heading2>
			<HousingFields :draft="draft" :errors="scopeErrors(errors, 'current_housing')" />
		</template>
	</EditablePanel>
</template>
