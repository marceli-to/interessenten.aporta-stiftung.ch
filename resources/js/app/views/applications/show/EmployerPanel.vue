<script setup>
import { useLookupsStore } from '@/stores/lookups'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'

// Employer slice of an applicant. Same `source` (the whole applicant) and same
// onSave as ApplicantPanel — it just edits draft.employer. Rendered only when an
// employer record exists; error keys are scoped under '<section>.employer'.
const props = defineProps({
	title: { type: String, default: 'Aktueller Arbeitgeber' },
	applicant: { type: Object, required: true },
	section: { type: String, required: true },
	onSave: { type: Function, required: true },
})

const lookups = useLookupsStore()
</script>

<template>
	<EditablePanel
		:title="title"
		:source="applicant"
		:errorPrefix="`${section}.employer`"
		:onSave="onSave"
	>
		<template #view="{ data }">
			<InfoList>
				<InfoRow label="Arbeitgeber">
					{{ data.employer?.name || '–' }}
				</InfoRow>
				<InfoRow label="Pensum">
					{{ data.employer?.workload_percent != null ? `${data.employer.workload_percent}%` : '–' }}
				</InfoRow>
				<InfoRow label="Jahreseinkommen">
					{{ lookups.label('income_brackets', data.employer?.annual_income_bracket) }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<InfoList v-if="draft.employer">
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
	</EditablePanel>
</template>
