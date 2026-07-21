<script setup>
import { useLookupsStore } from '@/stores/lookups'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EmployerFields from '@/views/applications/panels/fields/EmployerFields.vue'

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
			<EmployerFields :draft="draft" :errors="errors" />
		</template>
	</EditablePanel>
</template>
