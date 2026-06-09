<script setup>
import { useLookupsStore } from '@/stores/lookups'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
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
			<div v-if="draft.employer" class="space-y-15">
				<Group>
					<Label :error="errors.name">Arbeitgeber</Label>
					<Input v-model="draft.employer.name" :hasError="!!errors.name" />
				</Group>
				<Group>
					<Label :error="errors.workload_percent">Pensum (%)</Label>
					<Input v-model.number="draft.employer.workload_percent" type="number" :hasError="!!errors.workload_percent" />
				</Group>
				<Group>
					<Label :error="errors.annual_income_bracket">Jahreseinkommen</Label>
					<Select v-model="draft.employer.annual_income_bracket" :options="lookups.options('income_brackets')" :hasError="!!errors.annual_income_bracket" />
				</Group>
			</div>
		</template>
	</EditablePanel>
</template>
