<script setup>
import { RouterLink } from 'vue-router'

defineProps({
	id: { type: String, required: true },
})
</script>

<template>
	<div class="space-y-24">
		<div>
			<RouterLink :to="{ name: 'users.index' }" class="text-sm text-gray-500 hover:text-accent">
				← Zurück zur Liste
			</RouterLink>
		</div>

		<h1 class="text-xl font-semibold">Benutzer bearbeiten</h1>

		<form class="bg-white border border-gray-200 rounded-md p-24 space-y-16 max-w-xl">
			<div>
				<label class="block text-xs text-gray-500 mb-4">Name</label>
				<input type="text" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
			</div>
			<div>
				<label class="block text-xs text-gray-500 mb-4">E-Mail</label>
				<input type="email" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
			</div>
			<div>
				<label class="block text-xs text-gray-500 mb-4">Rolle</label>
				<select class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm">
					<option>—</option>
				</select>
			</div>

			<div class="flex items-center justify-between pt-8">
				<button type="button" class="px-12 py-8 border border-gray-200 rounded-md text-sm">
					Passwort zurücksetzen
				</button>
				<div class="flex gap-8">
					<button type="button" class="px-16 py-8 border border-gray-200 rounded-md text-sm">Abbrechen</button>
					<button type="submit" class="px-16 py-8 bg-accent text-white rounded-md text-sm">Speichern</button>
				</div>
			</div>
		</form>
	</div>
</template>
