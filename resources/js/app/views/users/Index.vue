<script setup>
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { PhPencil, PhTrash } from '@phosphor-icons/vue'
import { useUsersStore } from '@/stores/users'
import { useToast } from '@/composables/useToast'

const router = useRouter()
const store = useUsersStore()
const toast = useToast()

const roleLabels = {
	admin: 'Administrator',
	editor: 'Bearbeiter',
	viewer: 'Betrachter',
}

onMounted(() => store.fetch())

async function handleDelete(user) {
	if (!window.confirm(`"${user.firstname} ${user.name}" wirklich löschen?`)) return
	try {
		await store.destroy(user.id)
		toast.success('Benutzer gelöscht.')
	} catch {
		// failure already surfaced as a toast by the axios interceptor
	}
}
</script>

<template>
	<div class="space-y-24">
		<div class="flex items-center justify-between">
			<h1 class="text-xl font-semibold">Benutzer</h1>
			<RouterLink
				:to="{ name: 'users.create' }"
				class="px-16 py-8 bg-blue text-white rounded-md text-sm hover:opacity-90 transition-opacity"
			>
				Neuer Benutzer
			</RouterLink>
		</div>

		<div v-if="store.loading" class="text-sm text-gray-500">
			Laden …
		</div>

		<div v-else-if="store.users.length === 0" class="text-sm text-gray-500">
			Keine Benutzer vorhanden.
		</div>

		<div v-else class="bg-white border border-gray-200 rounded-md overflow-hidden">
			<table class="w-full text-sm">
				<thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
					<tr>
						<th class="px-16 py-12">Name</th>
						<th class="px-16 py-12">E-Mail</th>
						<th class="px-16 py-12">Rolle</th>
						<th class="px-16 py-12">Status</th>
						<th class="px-16 py-12 w-1"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<tr
						v-for="user in store.users"
						:key="user.id"
						class="hover:bg-gray-50"
					>
						<td class="px-16 py-12">
							{{ user.firstname }} {{ user.name }}
						</td>
						<td class="px-16 py-12 text-gray-600">{{ user.email }}</td>
						<td class="px-16 py-12 text-gray-600">{{ roleLabels[user.role] ?? user.role }}</td>
						<td class="px-16 py-12">
							<span
								class="inline-flex items-center gap-6 text-xs"
								:class="user.active ? 'text-emerald-700' : 'text-gray-400'"
							>
								<span
									class="size-6 rounded-full"
									:class="user.active ? 'bg-emerald-500' : 'bg-gray-300'"
								/>
								{{ user.active ? 'Aktiv' : 'Inaktiv' }}
							</span>
						</td>
						<td class="px-16 py-12">
							<div class="flex items-center justify-end gap-12">
								<button
									type="button"
									class="text-gray-400 hover:text-blue cursor-pointer transition-colors"
									@click="router.push({ name: 'users.edit', params: { id: user.id } })"
								>
									<PhPencil :size="16" weight="regular" />
								</button>
								<button
									type="button"
									class="text-gray-400 hover:text-red-600 cursor-pointer transition-colors"
									@click="handleDelete(user)"
								>
									<PhTrash :size="16" weight="regular" />
								</button>
							</div>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
