<script setup>
import { onMounted } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useUsersStore } from '@/stores/users'
import Heading1 from '@/components/ui/headings/H1.vue'
import Button from '@/components/ui/form/Button.vue'
import Panel from '@/components/ui/panels/Display.vue'
import TableHeadCell from '@/components/ui/table/HeadCell.vue'
import TableCell from '@/components/ui/table/Cell.vue'

const router = useRouter()
const store = useUsersStore()

onMounted(() => store.fetch())

// A row click opens the edit form — same interaction as the applications list.
function open(user) {
	router.push({ name: 'users.edit', params: { id: user.id } })
}
</script>

<template>
	<div class="flex items-center justify-between mb-30">
		<Heading1>Benutzer</Heading1>
		<RouterLink :to="{ name: 'users.create' }">
			<Button variant="primary" icon="plus" size="md">
				Neuer Benutzer
			</Button>
		</RouterLink>
	</div>

	<Panel>
		<div class="overflow-x-auto">
			<table class="w-full text-sm whitespace-nowrap">
				<thead class="text-left text-black border-b border-blue/20">
					<tr>
						<TableHeadCell variant="first">Vorname</TableHeadCell>
						<TableHeadCell>Name</TableHeadCell>
						<TableHeadCell variant="last">E-Mail</TableHeadCell>
					</tr>
				</thead>
				<tbody class="divide-y divide-blue/20">
					<template v-if="store.loading">
						<tr>
							<td colspan="3" class="py-30 text-sm text-light-gray">
								Laden …
							</td>
						</tr>
					</template>
					<template v-else>
						<tr
							v-for="user in store.users"
							:key="user.id"
							class="cursor-pointer hover:bg-light-gray/10 text-gray"
							@click="open(user)"
						>
							<TableCell variant="first" class="font-bold text-blue">
								{{ user.firstname }}
							</TableCell>
							<TableCell class="font-bold text-blue">
								{{ user.name }}
							</TableCell>
							<TableCell variant="last">
								{{ user.email }}
							</TableCell>
						</tr>
						<tr v-if="!store.users.length">
							<td colspan="3" class="py-30 text-center text-sm text-light-gray">
								Keine Benutzer vorhanden.
							</td>
						</tr>
					</template>
				</tbody>
			</table>
		</div>
	</Panel>
</template>
