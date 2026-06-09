<script setup>
import EditablePanel from '@/components/ui/panels/Editable.vue'
import InfoList from '@/components/ui/info/List.vue'
import InfoRow from '@/components/ui/info/Row.vue'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
import Input from '@/components/ui/form/Input.vue'
import Textarea from '@/components/ui/form/Textarea.vue'
import Checkbox from '@/components/ui/form/Checkbox.vue'

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
				<InfoRow label="Musikinstrumente">
					{{ data.info.plays_music ? (data.info.musical_instruments || 'Ja') : 'Keine' }}
				</InfoRow>
				<InfoRow label="Haustiere">
					{{ data.info.has_pets ? (data.info.pets_description || 'Ja') : 'Keine' }}
				</InfoRow>
				<InfoRow label="Bemerkungen">
					{{ data.info.remarks || '–' }}
				</InfoRow>
			</InfoList>
		</template>

		<template #edit="{ draft, errors }">
			<div class="space-y-15">
				<div class="grid grid-cols-2 gap-15">
					<Group>
						<Label :error="errors['household_info.adults_count']">Erwachsene</Label>
						<Input v-model.number="draft.info.adults_count" type="number" min="1" :hasError="!!errors['household_info.adults_count']" />
					</Group>
					<Group>
						<Label :error="errors['household_info.children_count']">Kinder</Label>
						<Input
							v-model.number="draft.info.children_count"
							type="number"
							min="0"
							:hasError="!!errors['household_info.children_count']"
							@input="syncChildren(draft)"
						/>
					</Group>
				</div>

				<Group v-if="draft.children.length">
					<Label :error="errors.children">Jahrgänge der Kinder</Label>
					<div class="grid grid-cols-3 gap-15">
						<Input
							v-for="(child, i) in draft.children"
							:key="i"
							v-model.number="child.birth_year"
							type="number"
							placeholder="Jahrgang"
						/>
					</div>
				</Group>

				<Group v-if="draft.children.length">
					<Checkbox v-model="draft.info.all_children_live_constantly">Alle Kinder leben dauerhaft im Haushalt</Checkbox>
				</Group>

				<Group>
					<Checkbox v-model="draft.info.plays_music">Musiziert</Checkbox>
				</Group>
				<Group v-if="draft.info.plays_music">
					<Label :error="errors['household_info.musical_instruments']">Musikinstrumente</Label>
					<Input v-model="draft.info.musical_instruments" :hasError="!!errors['household_info.musical_instruments']" />
				</Group>

				<Group>
					<Checkbox v-model="draft.info.has_pets">Haustiere</Checkbox>
				</Group>
				<Group v-if="draft.info.has_pets">
					<Label :error="errors['household_info.pets_description']">Beschreibung Haustiere</Label>
					<Input v-model="draft.info.pets_description" :hasError="!!errors['household_info.pets_description']" />
				</Group>

				<Group>
					<Label :error="errors['household_info.remarks']">Bemerkungen</Label>
					<Textarea v-model="draft.info.remarks" :rows="4" :hasError="!!errors['household_info.remarks']" />
				</Group>
			</div>
		</template>
	</EditablePanel>
</template>
