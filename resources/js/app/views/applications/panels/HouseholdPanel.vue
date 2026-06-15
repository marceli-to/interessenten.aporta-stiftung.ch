<script setup>
import { fmtYesNo } from '@/utils/format'
import { yesNoOptions } from '@/utils/options'
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import EditRow from '@/components/ui/info/EditRow.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import Textarea from '@/components/ui/form/Textarea.vue'

// Household + children. These are two separate backend sections (household_info,
// children) whose validation cross-checks each other, so they're saved together.
// `source` bundles them as { info, children }; the parent's onSave splits them
// back out and derives total_persons. Error keys keep their full path
// (household_info.* / children.*) — no errorPrefix.
const props = defineProps({
	source: { type: Object, required: true }, // { info: {...}, children: [...] }
	onSave: { type: Function, required: true },
})

// Resize the children list to match the entered count, keeping positions 1..n.
function syncChildren(draft) {
	const n = Math.max(0, Number(draft.info.children_count) || 0)
	const list = draft.children
	while (list.length < n) list.push({ position: list.length + 1, birth_year: null })
	list.length = n
	list.forEach((child, i) => (child.position = i + 1))
}

function personsLine(info) {
	const adults = info.adults_count ?? 0
	const children = info.children_count ?? 0
	const total = info.total_persons ?? adults + children
	return `${total} (${adults} Erwachsene, ${children} Kinder)`
}

function years(children) {
	return children?.length ? children.map((c) => c.birth_year).filter(Boolean).join(', ') : '–'
}

// The children row holds one input per child, so its errors come back keyed
// per index (children.0.birth_year, …) plus the list-level `children` (count
// mismatch). Surface the first of any so the row label turns red.
function childrenError(errors) {
	const key = Object.keys(errors).find((k) => k === 'children' || /^children\.\d+\./.test(k))
	return key ? errors[key] : null
}
</script>

<template>
	<EditablePanel title="Haushalt & weitere Angaben" :source="source" :onSave="onSave">
		<template #view="{ data }">
			<InfoList>
				<InfoRow label="Personen im Haushalt">
					{{ personsLine(data.info) }}
				</InfoRow>
				<InfoRow v-if="data.children?.length" label="Kinder (Jahrgänge)">
					{{ years(data.children) }}
				</InfoRow>
				<InfoRow v-if="data.children?.length" label="Kinder dauerhaft im Haushalt">
					{{ fmtYesNo(data.info.all_children_live_constantly) }}
				</InfoRow>
				<InfoRow label="Haustiere">
					{{ data.info.has_pets ? (data.info.pets_description || 'Ja') : 'Keine' }}
				</InfoRow>
				<InfoRow label="Bemerkungen" class="items-start!">
					{{ data.info.remarks || '–' }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<InfoList>
				<EditRow label="Erwachsene" :error="errors['household_info.adults_count']">
					<Input v-model.number="draft.info.adults_count" type="number" min="1" :hasError="!!errors['household_info.adults_count']" />
				</EditRow>
				<EditRow label="Kinder" :error="errors['household_info.children_count']">
					<Input
						v-model.number="draft.info.children_count"
						type="number"
						min="0"
						:hasError="!!errors['household_info.children_count']"
						@input="syncChildren(draft)"
					/>
				</EditRow>

				<EditRow v-if="draft.children.length" label="Jahrgänge der Kinder" :error="childrenError(errors)">
					<div class="grid grid-cols-3 gap-15">
						<Input
							v-for="(child, i) in draft.children"
							:key="i"
							v-model.number="child.birth_year"
							type="number"
							placeholder="Jahrgang"
							:hasError="!!errors[`children.${i}.birth_year`]"
						/>
					</div>
				</EditRow>

				<EditRow v-if="draft.children.length" label="Kinder dauerhaft im Haushalt">
					<Select v-model="draft.info.all_children_live_constantly" :options="yesNoOptions" />
				</EditRow>

				<EditRow label="Haustiere">
					<Select v-model="draft.info.has_pets" :options="yesNoOptions" />
				</EditRow>
				<EditRow v-if="draft.info.has_pets" label="Beschreibung Haustiere" :error="errors['household_info.pets_description']">
					<Input v-model="draft.info.pets_description" :hasError="!!errors['household_info.pets_description']" />
				</EditRow>

				<EditRow label="Bemerkungen" :error="errors['household_info.remarks']" class="items-start!">
					<Textarea v-model="draft.info.remarks" :rows="4" :hasError="!!errors['household_info.remarks']" class="py-5!" />
				</EditRow>
			</InfoList>
		</template>
	</EditablePanel>
</template>
