<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtDate, fmtMoney, fmtYesNo } from '@/utils/format'
import { yesNoOptions } from '@/utils/options'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import ChipGroup from '@/components/ui/form/ChipGroup.vue'

// Rental wish — a clean standalone section on the application.
const props = defineProps({
	source: { type: Object, required: true },
	onSave: { type: Function, required: true },
})

const lookups = useLookupsStore()
</script>

<template>
	<EditablePanel title="Mietwunsch" :source="source" errorPrefix="housing_wish" :onSave="onSave">
		<template #view="{ data }">
			<InfoList>
				<InfoRow label="Frühester Mietbeginn">
					{{ fmtDate(data.earliest_move_in) }}
				</InfoRow>
				<InfoRow label="Max. Bruttomiete">
					{{ fmtMoney(data.max_gross_rent) }}
				</InfoRow>
				<InfoRow label="Stadtkreise">
					<ChipGroup class="py-3" :modelValue="data.districts" :options="lookups.options('districts')" readonly />
				</InfoRow>
				<InfoRow label="Stockwerke">
					<ChipGroup class="py-3" :modelValue="data.floors" :options="lookups.options('floors')" readonly />
				</InfoRow>
				<InfoRow label="Zimmer (Personen ± 1)">
					<ChipGroup class="py-3" :modelValue="data.rooms" :options="lookups.options('rooms')" readonly />
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<InfoList>
				<EditRow label="Frühester Mietbeginn" :error="errors.earliest_move_in">
					<Input v-model="draft.earliest_move_in" type="date" :hasError="!!errors.earliest_move_in" />
				</EditRow>
				<EditRow label="Max. Bruttomiete" :error="errors.max_gross_rent">
					<Input v-model.number="draft.max_gross_rent" type="number" :hasError="!!errors.max_gross_rent" />
				</EditRow>
				<EditRow label="Stadtkreise" :error="errors.districts">
					<ChipGroup class="py-3" v-model="draft.districts" :options="lookups.options('districts')" />
				</EditRow>
				<EditRow label="Stockwerke" :error="errors.floors">
					<ChipGroup class="py-3" v-model="draft.floors" :options="lookups.options('floors')" />
				</EditRow>
				<InfoRow label="Zimmer (Personen ± 1)">
					<ChipGroup class="py-3" :modelValue="draft.rooms" :options="lookups.options('rooms')" readonly />
				</InfoRow>
			</InfoList>
		</template>
	</EditablePanel>
</template>
