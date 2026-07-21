<script setup>
import { useLookupsStore } from '@/stores/lookups'
import InfoList from '@/components/ui/info/List.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'

// Employer form fields, edited in place on `draft.employer`. `draft` is the whole
// applicant so the block can be scaffolded when it's missing — an applicant who
// never had an employer (or a brand-new co-applicant) arrives with employer: null
// and would otherwise have nothing to bind to.
const props = defineProps({
	draft: { type: Object, required: true },
	errors: { type: Object, default: () => ({}) },
})

if (!props.draft.employer) {
	props.draft.employer = { name: '', workload_percent: null, annual_income_bracket: null }
}

const lookups = useLookupsStore()
</script>

<template>
	<InfoList>
		<EditRow label="Arbeitgeber" :error="errors.name">
			<Input v-model="draft.employer.name" :hasError="!!errors.name" />
		</EditRow>
		<EditRow label="Pensum (%)" :error="errors.workload_percent">
			<Input v-model.number="draft.employer.workload_percent" type="number" :hasError="!!errors.workload_percent" />
		</EditRow>
		<EditRow label="Jahreseinkommen" :error="errors.annual_income_bracket">
			<Select v-model="draft.employer.annual_income_bracket" :options="lookups.options('income_brackets')" :hasError="!!errors.annual_income_bracket" />
		</EditRow>
	</InfoList>
</template>
