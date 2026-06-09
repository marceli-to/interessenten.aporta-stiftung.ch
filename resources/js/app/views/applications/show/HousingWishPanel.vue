<script setup>
import { useLookupsStore } from '@/stores/lookups'
import { fmtDate, fmtMoney } from '@/utils/format'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
import Input from '@/components/ui/form/Input.vue'
import Checkbox from '@/components/ui/form/Checkbox.vue'
import ChipGroup from '@/components/ui/form/ChipGroup.vue'

// Rental wish — a clean standalone section on the application.
const props = defineProps({
	source: { type: Object, required: true },
	onSave: { type: Function, required: true },
})

const lookups = useLookupsStore()

const yesNo = (v) => (v == null ? '–' : v ? 'Ja' : 'Nein')
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
					<div v-if="data.districts?.length" class="flex flex-wrap gap-8">
						<span v-for="slug in data.districts" :key="slug" class="h-30 px-10 inline-flex items-center rounded-md border border-blue/40 text-blue text-sm">
							{{ lookups.label('districts', slug) }}
						</span>
					</div>
					<template v-else>–</template>
				</InfoRow>
				<InfoRow label="Stockwerke">
					<div v-if="data.floors?.length" class="flex flex-wrap gap-8">
						<span v-for="slug in data.floors" :key="slug" class="h-30 px-10 inline-flex items-center rounded-md border border-blue/40 text-blue text-sm">
							{{ lookups.label('floors', slug) }}
						</span>
					</div>
					<template v-else>–</template>
				</InfoRow>
				<InfoRow label="Anzahl Zimmer">
					<div v-if="data.rooms?.length" class="flex flex-wrap gap-8">
						<span v-for="slug in data.rooms" :key="slug" class="h-30 px-10 inline-flex items-center rounded-md border border-blue/40 text-blue text-sm">
							{{ lookups.label('rooms', slug) }}
						</span>
					</div>
					<template v-else>–</template>
				</InfoRow>
				<InfoRow label="Balkon">
					{{ yesNo(data.wants_balcony) }}
				</InfoRow>
				<InfoRow label="Lift">
					{{ yesNo(data.wants_elevator) }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<div class="space-y-15">
				<Group>
					<Label :error="errors.earliest_move_in">Frühester Mietbeginn</Label>
					<Input v-model="draft.earliest_move_in" type="date" :hasError="!!errors.earliest_move_in" />
				</Group>
				<Group>
					<Label :error="errors.max_gross_rent">Max. Bruttomiete</Label>
					<Input v-model.number="draft.max_gross_rent" type="number" :hasError="!!errors.max_gross_rent" />
				</Group>
				<Group>
					<Label :error="errors.districts">Stadtkreise</Label>
					<ChipGroup v-model="draft.districts" :options="lookups.options('districts')" />
				</Group>
				<Group>
					<Label :error="errors.floors">Stockwerke</Label>
					<ChipGroup v-model="draft.floors" :options="lookups.options('floors')" />
				</Group>
				<Group>
					<Label :error="errors.rooms">Anzahl Zimmer</Label>
					<ChipGroup v-model="draft.rooms" :options="lookups.options('rooms')" />
				</Group>
				<Group>
					<Checkbox v-model="draft.wants_balcony">Balkon gewünscht</Checkbox>
				</Group>
				<Group>
					<Checkbox v-model="draft.wants_elevator">Lift gewünscht</Checkbox>
				</Group>
			</div>
		</template>
	</EditablePanel>
</template>
