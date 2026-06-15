<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '@/api/users'
import { useUsersStore } from '@/stores/users'
import { useToast } from '@/composables/useToast'
import Heading1 from '@/components/ui/headings/H1.vue'
import Panel from '@/components/ui/panels/Display.vue'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
import Input from '@/components/ui/form/Input.vue'
import Button from '@/components/ui/form/Button.vue'
import ConfirmDialog from '@/components/ui/dialog/ConfirmDialog.vue'

const props = defineProps({
	id: { type: String, default: null },
})

const router = useRouter()
const store = useUsersStore()
const toast = useToast()

const isEdit = computed(() => props.id !== null)

const form = reactive({
	firstname: '',
	name: '',
	email: '',
	password: '',
})

const saving = ref(false)
const loading = ref(false)
const passwordVisible = ref(false)

store.clearErrors()
onUnmounted(() => store.clearErrors())

onMounted(async () => {
	if (!isEdit.value) return
	loading.value = true
	try {
		const { data } = await api.show(props.id)
		const user = data.data
		form.firstname = user.firstname
		form.name = user.name
		form.email = user.email
		form.password = ''
	} finally {
		loading.value = false
	}
})

function generatePassword() {
	// XXXX-XXXX-XXXX from an unambiguous uppercase set: no I/O (letters) or 0/1
	// (digits), so the password is safe to read aloud or copy from a note.
	const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'
	const groups = 3
	const groupLength = 4
	const bytes = new Uint32Array(groups * groupLength)
	crypto.getRandomValues(bytes)

	const parts = []
	for (let g = 0; g < groups; g++) {
		let part = ''
		for (let i = 0; i < groupLength; i++) {
			part += chars[bytes[g * groupLength + i] % chars.length]
		}
		parts.push(part)
	}

	form.password = parts.join('-')
	passwordVisible.value = true
	delete store.errors.password
}

async function handleSubmit() {
	saving.value = true
	store.clearErrors()
	try {
		const payload = { ...form }
		if (isEdit.value && !payload.password) delete payload.password
		if (isEdit.value) {
			await api.update(props.id, payload)
		} else {
			await api.store(payload)
		}
		toast.success(isEdit.value ? 'Benutzer aktualisiert.' : 'Benutzer erstellt.')
		router.push({ name: 'users.index' })
	} catch (e) {
		if (e.response?.status === 422) {
			// validation: highlight fields inline + a gentle nudge toast
			store.setErrors(e.response.data.errors)
			toast.error('Bitte überprüfen Sie Ihre Eingaben.')
		}
		// any other failure (auth/permission/server/network) was already
		// surfaced as a toast by the axios interceptor — just stay on the form
	} finally {
		saving.value = false
	}
}

// Deletion is confirmed through a ConfirmDialog, mirroring the application detail.
const confirmingDelete = ref(false)

const askDelete = () => { confirmingDelete.value = true }

async function handleDelete() {
	try {
		await api.destroy(props.id)
		toast.success('Benutzer gelöscht.')
		router.push({ name: 'users.index' })
	} catch {
		// failure already surfaced as a toast by the axios interceptor
		confirmingDelete.value = false
	}
}
</script>

<template>
	<div>
		<header class="flex items-center justify-between mb-30">
			<Heading1>{{ isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}</Heading1>
			<RouterLink :to="{ name: 'users.index' }">
				<Button variant="ghost" size="sm">← Zurück zur Liste</Button>
			</RouterLink>
		</header>

		<div v-if="loading" class="text-sm text-light-gray">Laden …</div>

		<div v-else class="grid grid-cols-12 gap-30">
			<div class="col-span-8 flex flex-col gap-30">
				<Panel>
					<form class="space-y-16" @submit.prevent="handleSubmit">
						<div class="grid grid-cols-2 gap-16">
							<Group>
								<Label for="firstname" :error="store.errors.firstname">Vorname *</Label>
								<Input
									id="firstname"
									v-model="form.firstname"
									:hasError="!!store.errors.firstname"
									@focus="delete store.errors.firstname"
									class="min-h-36!"
								/>
							</Group>

							<Group>
								<Label for="name" :error="store.errors.name">Name *</Label>
								<Input
									id="name"
									v-model="form.name"
									:hasError="!!store.errors.name"
									@focus="delete store.errors.name"
									class="min-h-36!"
								/>
							</Group>
						</div>

						<Group>
							<Label for="email" :error="store.errors.email">E-Mail *</Label>
							<Input
								id="email"
								v-model="form.email"
								type="email"
								:hasError="!!store.errors.email"
								@focus="delete store.errors.email"
								class="min-h-36!"
							/>
						</Group>

						<Group>
							<Label for="password" :error="store.errors.password">
								{{ isEdit ? 'Passwort (leer lassen um beizubehalten)' : 'Passwort *' }}
							</Label>
							<Input
								id="password"
								v-model="form.password"
								:type="passwordVisible ? 'text' : 'password'"
								:hasError="!!store.errors.password"
								@focus="passwordVisible = true; delete store.errors.password"
								@blur="passwordVisible = false"
								class="min-h-36!"
							/>
							<div class="mt-10">
								<Button variant="ghost" size="sm" type="button" icon="arrows-clockwise" @click="generatePassword">
									Passwort generieren
								</Button>
							</div>
						</Group>

						<div class="flex items-center justify-end gap-10 pt-8">
							<RouterLink :to="{ name: 'users.index' }">
								<Button variant="ghost" size="sm" type="button">Abbrechen</Button>
							</RouterLink>
							<Button type="submit" size="sm" icon="floppy-disk" :disabled="saving">
								{{ saving ? 'Speichern …' : 'Speichern' }}
							</Button>
						</div>
					</form>
				</Panel>
			</div>

			<div v-if="isEdit" class="col-span-4 flex flex-col gap-30">
				<Panel variant="danger" title="Benutzer löschen">
					<p class="text-sm text-black">
						Der Benutzer wird dauerhaft gelöscht. Dieser Vorgang kann nicht
						rückgängig gemacht werden.
					</p>
					<div class="mt-15">
						<Button variant="danger-solid" size="sm" icon="trash" @click="askDelete">
							Löschen
						</Button>
					</div>
				</Panel>
			</div>
		</div>

		<ConfirmDialog
			:open="confirmingDelete"
			title="Benutzer löschen"
			:message="`«${form.firstname} ${form.name}» wird dauerhaft gelöscht.`"
			confirmLabel="Löschen bestätigen"
			cancelLabel="Abbrechen"
			:destructive="true"
			@confirm="handleDelete"
			@cancel="confirmingDelete = false"
		/>
	</div>
</template>
