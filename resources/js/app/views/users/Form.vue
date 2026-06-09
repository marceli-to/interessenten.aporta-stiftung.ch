<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '@/api/users'
import { useUsersStore } from '@/stores/users'
import Group from '@/components/ui/form/Group.vue'
import Label from '@/components/ui/form/Label.vue'
import Input from '@/components/ui/form/Input.vue'
import Select from '@/components/ui/form/Select.vue'
import Checkbox from '@/components/ui/form/Checkbox.vue'
import Button from '@/components/ui/form/Button.vue'
import { useToast } from '@/composables/useToast'

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
	role: 'admin',
	active: true,
})

const saving = ref(false)
const loading = ref(false)
const passwordVisible = ref(false)

const roleOptions = [
	{ value: 'admin', label: 'Administrator' },
	{ value: 'editor', label: 'Bearbeiter' },
	{ value: 'viewer', label: 'Betrachter' },
]

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
		form.role = user.role
		form.active = user.active
		form.password = ''
	} finally {
		loading.value = false
	}
})

function generatePassword() {
	const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%&*'
	const bytes = new Uint32Array(16)
	crypto.getRandomValues(bytes)
	let pw = ''
	for (let i = 0; i < bytes.length; i++) pw += chars[bytes[i] % chars.length]
	form.password = pw
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
</script>

<template>
	<div class="space-y-24">
		<div>
			<RouterLink :to="{ name: 'users.index' }" class="text-sm text-gray-500 hover:text-blue">
				← Zurück zur Liste
			</RouterLink>
		</div>

		<h1 class="text-xl font-semibold">
			{{ isEdit ? 'Benutzer bearbeiten' : 'Neuer Benutzer' }}
		</h1>

		<div v-if="loading" class="text-sm text-gray-500">Laden …</div>

		<form
			v-else
			class="bg-white border border-gray-200 rounded-md p-24 space-y-16 max-w-xl"
			@submit.prevent="handleSubmit"
		>
			<div class="grid grid-cols-2 gap-16">
				<Group>
					<Label for="firstname" :error="store.errors.firstname">Vorname *</Label>
					<Input
						id="firstname"
						v-model="form.firstname"
						:hasError="!!store.errors.firstname"
						@focus="delete store.errors.firstname"
					/>
				</Group>

				<Group>
					<Label for="name" :error="store.errors.name">Name *</Label>
					<Input
						id="name"
						v-model="form.name"
						:hasError="!!store.errors.name"
						@focus="delete store.errors.name"
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
				/>
				<button
					type="button"
					class="mt-4 text-xs text-gray-400 hover:text-gray-900 transition-colors cursor-pointer"
					@click="generatePassword"
				>
					Passwort generieren
				</button>
			</Group>

			<Group>
				<Label for="role">Rolle</Label>
				<Select
					id="role"
					v-model="form.role"
					:options="roleOptions"
					:placeholder="null"
				/>
			</Group>

			<Group>
				<Checkbox v-model="form.active">Aktiv</Checkbox>
			</Group>

			<div class="flex items-center justify-end gap-8 pt-8">
				<RouterLink :to="{ name: 'users.index' }">
					<Button variant="outline" type="button">Abbrechen</Button>
				</RouterLink>
				<Button type="submit" icon="floppy-disk" :disabled="saving">
					{{ saving ? 'Speichern …' : 'Speichern' }}
				</Button>
			</div>
		</form>
	</div>
</template>
