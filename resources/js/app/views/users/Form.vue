<script setup>
import { computed, onMounted, onUnmounted, reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import api from '@/api/users'
import { useUsersStore } from '@/stores/users'
import FormGroup from '@/components/ui/form/FormGroup.vue'
import FormLabel from '@/components/ui/form/FormLabel.vue'
import FormInput from '@/components/ui/form/FormInput.vue'
import FormSelect from '@/components/ui/form/FormSelect.vue'
import FormCheckbox from '@/components/ui/form/FormCheckbox.vue'
import FormButton from '@/components/ui/form/FormButton.vue'

const props = defineProps({
	id: { type: String, default: null },
})

const router = useRouter()
const store = useUsersStore()

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
		router.push({ name: 'users.index' })
	} catch (e) {
		if (e.response?.status === 422) {
			store.setErrors(e.response.data.errors)
		} else {
			throw e
		}
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
				<FormGroup>
					<FormLabel for="firstname" :error="store.errors.firstname">Vorname *</FormLabel>
					<FormInput
						id="firstname"
						v-model="form.firstname"
						:hasError="!!store.errors.firstname"
						@focus="delete store.errors.firstname"
					/>
				</FormGroup>

				<FormGroup>
					<FormLabel for="name" :error="store.errors.name">Name *</FormLabel>
					<FormInput
						id="name"
						v-model="form.name"
						:hasError="!!store.errors.name"
						@focus="delete store.errors.name"
					/>
				</FormGroup>
			</div>

			<FormGroup>
				<FormLabel for="email" :error="store.errors.email">E-Mail *</FormLabel>
				<FormInput
					id="email"
					v-model="form.email"
					type="email"
					:hasError="!!store.errors.email"
					@focus="delete store.errors.email"
				/>
			</FormGroup>

			<FormGroup>
				<FormLabel for="password" :error="store.errors.password">
					{{ isEdit ? 'Passwort (leer lassen um beizubehalten)' : 'Passwort *' }}
				</FormLabel>
				<FormInput
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
			</FormGroup>

			<FormGroup>
				<FormLabel for="role">Rolle</FormLabel>
				<FormSelect
					id="role"
					v-model="form.role"
					:options="roleOptions"
					:placeholder="null"
				/>
			</FormGroup>

			<FormGroup>
				<FormCheckbox v-model="form.active">Aktiv</FormCheckbox>
			</FormGroup>

			<div class="flex items-center justify-end gap-8 pt-8">
				<RouterLink :to="{ name: 'users.index' }">
					<FormButton variant="secondary" type="button">Abbrechen</FormButton>
				</RouterLink>
				<FormButton type="submit" :disabled="saving">
					{{ saving ? 'Speichern …' : 'Speichern' }}
				</FormButton>
			</div>
		</form>
	</div>
</template>
