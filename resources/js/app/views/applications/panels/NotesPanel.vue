<script setup>
import { onMounted, onUnmounted, ref } from 'vue'
import api from '@/api/applications'
import { useToast } from '@/composables/useToast'
import { fmtDate } from '@/utils/format'
import Panel from '@/components/ui/panels/Display.vue'
import Button from '@/components/ui/form/Button.vue'
import Textarea from '@/components/ui/form/Textarea.vue'

// The "Notizen" sidebar panel — a self-contained resource. Unlike the section
// panels it isn't a slice of the canonical `app` object: it owns its own list
// and talks to the notes endpoints directly, so adding/editing/deleting a note
// never round-trips the whole application. The `notes` prop is only the initial
// list (delivered with the application detail); from there `items` is the source
// of truth. Each endpoint returns just the affected note (or 204 on delete).
//   - add:    header toggles to a white textarea + Speichern → unshift (newest first)
//   - edit:   the "…" reveals Bearbeiten, which swaps the body for a textarea
//   - delete: the "…" reveals Löschen, which removes the note → filter out
// Only one of {adding, editing} is ever active; starting one cancels the other.
// Each note's "…" reveals its action buttons; only one note's actions open at a time.
const props = defineProps({
	applicationId: { type: [Number, String], required: true },
	notes: { type: Array, default: () => [] },
})

const toast = useToast()

// Local, authoritative copy of the list. Seeded once from the prop; notes are
// only ever mutated through this panel, so it never needs to re-sync.
const items = ref([...props.notes])

const adding = ref(false)
const newBody = ref('')
const addError = ref(null)

const editingId = ref(null)
const editBody = ref('')
const editError = ref(null)

// Id of the note whose action buttons (Bearbeiten/Löschen) are revealed.
const openActionsId = ref(null)
const saving = ref(false)

function startAdd() {
	cancelEdit()
	openActionsId.value = null
	adding.value = true
	newBody.value = ''
	addError.value = null
}

function cancelAdd() {
	adding.value = false
	newBody.value = ''
	addError.value = null
}

async function saveNew() {
	if (!newBody.value.trim() || saving.value) return
	saving.value = true
	addError.value = null
	try {
		const { data } = await api.storeNote(props.applicationId, { body: newBody.value.trim() })
		items.value.unshift(data.data) // newest first
		toast.success('Notiz hinzugefügt.')
		cancelAdd()
	} catch (e) {
		addError.value = bodyError(e)
	} finally {
		saving.value = false
	}
}

function startEdit(note) {
	cancelAdd()
	openActionsId.value = null
	editingId.value = note.id
	editBody.value = note.body
	editError.value = null
}

function cancelEdit() {
	editingId.value = null
	editBody.value = ''
	editError.value = null
}

async function saveEdit(note) {
	if (!editBody.value.trim() || saving.value) return
	saving.value = true
	editError.value = null
	try {
		const { data } = await api.updateNote(props.applicationId, note.id, { body: editBody.value.trim() })
		const i = items.value.findIndex((n) => n.id === note.id)
		if (i !== -1) items.value[i] = data.data
		toast.success('Notiz aktualisiert.')
		cancelEdit()
	} catch (e) {
		editError.value = bodyError(e)
	} finally {
		saving.value = false
	}
}

async function remove(note) {
	if (saving.value) return
	saving.value = true
	try {
		await api.destroyNote(props.applicationId, note.id)
		items.value = items.value.filter((n) => n.id !== note.id)
		if (editingId.value === note.id) cancelEdit()
		toast.success('Notiz gelöscht.')
	} finally {
		saving.value = false
	}
}

// 422 from the single `body` field; any other failure is already toasted by the
// axios interceptor, so fall back to a generic inline hint.
function bodyError(e) {
	if (e?.response?.status !== 422) return 'Speichern fehlgeschlagen.'
	return e.response.data?.errors?.body?.[0] ?? 'Bitte Text eingeben.'
}

function toggleActions(id) {
	openActionsId.value = openActionsId.value === id ? null : id
}

// Click anywhere else collapses the revealed actions back to the "…". The "…"
// trigger and the action row stop propagation so a click on them doesn't
// immediately re-close.
const closeActions = () => (openActionsId.value = null)
onMounted(() => document.addEventListener('click', closeActions))
onUnmounted(() => document.removeEventListener('click', closeActions))
</script>

<template>
	<Panel variant="highlight" title="Notizen">
		<template #action>
			<div v-if="adding" class="flex items-center gap-10">
				<Button variant="ghost" size="sm" @click="cancelAdd">
					Abbrechen
				</Button>
				<Button variant="ghost" size="sm" icon="floppy-disk" :disabled="!newBody.trim() || saving" @click="saveNew">
					Speichern
				</Button>
			</div>
			<Button v-else-if="editingId === null" variant="ghost" size="sm" icon="note-pencil" @click="startAdd">
				Neue Notiz
			</Button>
		</template>

		<!-- New note: white field divided from the header, above the list. -->
		<div v-if="adding" class="border-t border-blue/20 pt-15 pb-5">
			<Textarea v-model="newBody" variant="white" :rows="5" :hasError="!!addError" />
			<div v-if="addError" class="mt-5 text-sm text-red">
				{{ addError }}
			</div>
		</div>

		<!-- Each note carries the divider above it; the first one's doubles as the header rule (or the rule under the new-note field). -->
    <div class="divide-y divide-black/20 border-y border-black/20">
      <template v-if="items.length === 0 && !adding">
        <div class="py-10">
         Noch keine Notizen...
        </div>
      </template>
      <template v-else>
        <div v-for="note in items" :key="note.id" class="py-10">
          <div class="flex items-baseline justify-between gap-10">
            <span class="font-bold text-blue">
              {{ note.author }}
            </span>
            <span class="shrink-0 text-sm text-black/50">
              {{ fmtDate(note.created_at) }}
            </span>
          </div>

          <div v-if="editingId === note.id" class="mt-10">
            <Textarea v-model="editBody" variant="white" :rows="4" :hasError="!!editError" />
            <p v-if="editError" class="mt-5 text-sm text-red">
              {{ editError }}
            </p>
            <div class="mt-10 flex items-center justify-end gap-10">
              <Button variant="ghost" size="sm" @click="cancelEdit">
                Abbrechen
              </Button>
              <Button variant="ghost" size="sm" icon="floppy-disk" :disabled="!editBody.trim() || saving" @click="saveEdit(note)">
                Speichern
              </Button>
            </div>
          </div>

          <template v-else>
            <p class="mt-5 whitespace-pre-line text-blue">{{ note.body }}</p>
            <div class="flex h-30 items-center justify-end" @click.stop>
              <Button
                v-if="openActionsId !== note.id"
                variant="ghost"
                size="sm"
                icon="dots-three"
                title="Aktionen"
                @click="toggleActions(note.id)"
              />
              <div v-else class="flex items-center gap-15">
                <Button variant="ghost" size="sm" icon="pencil-simple" @click="startEdit(note)">
                  Bearbeiten
                </Button>
                <Button variant="danger" size="sm" icon="trash" @click="remove(note)">
                  Löschen
                </Button>
              </div>
            </div>
          </template>
        </div>
      </template>
    </div>
	</Panel>
</template>
