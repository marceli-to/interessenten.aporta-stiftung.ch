<script setup>
import { ref } from 'vue'
import Panel from '@/components/ui/panels/Display.vue'
import Button from '@/components/ui/form/Button.vue'

// A Panel that flips between a read-only #view slot and an editable #edit slot.
// It owns the whole edit lifecycle so the panels themselves stay declarative:
// cloning the source into a throwaway draft on "Bearbeiten", restoring it on
// "Abbrechen", and on "Speichern" awaiting `onSave(draft)` — closing on success,
// or mapping a 422 response into per-field `errors` exposed to the #edit slot.
const props = defineProps({
	title: { type: String, default: null },
	// Current persisted value for this section. Cloned into the draft on edit.
	source: { type: [Object, Array], default: () => ({}) },
	// async (draft) => Promise — performs the PUT. Rejects with the axios error.
	onSave: { type: Function, required: true },
	// Stripped from 422 error keys so the #edit slot can key errors by field
	// path relative to the section (e.g. 'main_applicant.' → 'first_name').
	errorPrefix: { type: String, default: null },
	editable: { type: Boolean, default: true },
	// Wording of the button that opens the form. A panel that creates a record
	// rather than editing one says so ("Partner*in hinzufügen").
	editLabel: { type: String, default: 'Bearbeiten' },
	editIcon: { type: String, default: 'pencil-simple' },
})

const isEditing = ref(false)
const saving = ref(false)
const draft = ref(null)
const errors = ref({})

function clone(value) {
	return value == null ? value : JSON.parse(JSON.stringify(value))
}

function startEdit() {
	draft.value = clone(props.source)
	errors.value = {}
	isEditing.value = true
}

function cancel() {
	isEditing.value = false
	draft.value = null
	errors.value = {}
}

// The moment the user focuses a field to correct it, drop the stale validation
// errors — label colour, control tint and info icon all derive from this object,
// so clearing it makes every error vanish at once. The next Save re-validates and
// repopulates from the server, so nothing is lost. Only real form controls count:
// focusing the error's info button must NOT wipe the message it's about to show.
function clearErrors(e) {
	if (!['INPUT', 'SELECT', 'TEXTAREA'].includes(e.target?.tagName)) return
	if (Object.keys(errors.value).length) errors.value = {}
}

async function save() {
	saving.value = true
	try {
		await props.onSave(draft.value)
		cancel()
	} catch (e) {
		// 422 → inline field errors. Every other failure was already surfaced as
		// a toast by the axios interceptor, so we just stay open for a retry.
		if (e?.response?.status === 422) {
			errors.value = mapErrors(e.response.data.errors)
		}
	} finally {
		saving.value = false
	}
}

function mapErrors(raw) {
	const prefix = props.errorPrefix ? `${props.errorPrefix}.` : ''
	const out = {}
	for (const [key, messages] of Object.entries(raw ?? {})) {
		const field = prefix && key.startsWith(prefix) ? key.slice(prefix.length) : key
		out[field] = Array.isArray(messages) ? messages[0] : messages
	}
	return out
}
</script>

<template>
	<Panel :title="title">
		<template v-if="editable" #action>
			<div v-if="isEditing" class="flex items-center gap-10">
				<Button variant="ghost" size="sm" @click="cancel">
					Abbrechen
				</Button>
				<Button variant="primary" size="sm" icon="floppy-disk" :disabled="saving" @click="save">
					{{ saving ? 'Speichern …' : 'Speichern' }}
				</Button>
			</div>
			<div v-else class="flex items-center gap-10">
				<!-- Panel-specific extras (e.g. "Entfernen"), only while not editing. -->
				<slot name="action" />
				<Button variant="outline" size="sm" :icon="editIcon" @click="startEdit">
					{{ editLabel }}
				</Button>
			</div>
		</template>

		<div v-if="isEditing" @focusin="clearErrors">
			<slot name="edit" :draft="draft" :errors="errors" />
		</div>
		<slot v-else name="view" :data="source" />
	</Panel>
</template>
