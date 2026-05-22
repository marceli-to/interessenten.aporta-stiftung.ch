<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'

defineProps({
	id: { type: String, required: true },
})

const tab = ref('details')
const tabs = [
	{ key: 'details', label: 'Details' },
	{ key: 'notes', label: 'Notizen' },
	{ key: 'history', label: 'Verlauf' },
]
</script>

<template>
	<div class="space-y-24">
		<div>
			<RouterLink :to="{ name: 'applications.index' }" class="text-sm text-gray-500 hover:text-accent">
				← Zurück zur Liste
			</RouterLink>
		</div>

		<div class="bg-white border border-gray-200 rounded-md p-16 flex flex-wrap items-center gap-16">
			<div class="flex items-center gap-12">
				<span class="text-xs uppercase text-gray-500">Referenz</span>
				<span class="font-mono text-sm">#{{ id }}</span>
			</div>
			<span class="inline-flex items-center px-8 py-4 rounded-md bg-gray-100 text-xs text-gray-600">
				Status: —
			</span>
			<label class="flex items-center gap-8 text-sm">
				<input type="checkbox" /> Markiert
			</label>
			<div class="flex items-center gap-8 text-sm">
				<span class="text-xs text-gray-500">Zuständig</span>
				<select class="border border-gray-200 rounded-md px-8 py-4 text-sm">
					<option>—</option>
				</select>
			</div>
			<div class="ml-auto flex items-center gap-8">
				<button class="px-12 py-4 border border-gray-200 rounded-md text-sm" disabled>PDF</button>
				<button class="px-12 py-4 border border-gray-200 rounded-md text-sm" disabled>Excel</button>
				<button class="px-12 py-4 border border-gray-200 rounded-md text-sm">⋯</button>
			</div>
		</div>

		<div class="border-b border-gray-200">
			<nav class="flex gap-24 text-sm">
				<button
					v-for="t in tabs"
					:key="t.key"
					@click="tab = t.key"
					class="py-8"
					:class="tab === t.key ? 'border-b-2 border-accent' : 'text-gray-500'"
				>
					{{ t.label }}
				</button>
			</nav>
		</div>

		<section v-if="tab === 'details'" class="space-y-32">
			<fieldset class="bg-white border border-gray-200 rounded-md p-24 space-y-16">
				<legend class="text-sm font-semibold px-8">Persönliche Angaben</legend>
				<div class="grid grid-cols-2 gap-16">
					<div>
						<label class="block text-xs text-gray-500 mb-4">Anrede</label>
						<select class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm"><option>—</option></select>
					</div>
					<div>
						<label class="block text-xs text-gray-500 mb-4">Zivilstand</label>
						<select class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm"><option>—</option></select>
					</div>
					<div>
						<label class="block text-xs text-gray-500 mb-4">Vorname</label>
						<input type="text" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
					</div>
					<div>
						<label class="block text-xs text-gray-500 mb-4">Nachname</label>
						<input type="text" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
					</div>
				</div>
			</fieldset>

			<fieldset class="bg-white border border-gray-200 rounded-md p-24 space-y-16">
				<legend class="text-sm font-semibold px-8">Adresse</legend>
				<div class="grid grid-cols-2 gap-16">
					<div class="col-span-2">
						<label class="block text-xs text-gray-500 mb-4">Strasse</label>
						<input type="text" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
					</div>
					<div>
						<label class="block text-xs text-gray-500 mb-4">PLZ</label>
						<input type="text" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
					</div>
					<div>
						<label class="block text-xs text-gray-500 mb-4">Ort</label>
						<input type="text" class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm" />
					</div>
				</div>
			</fieldset>

			<div class="flex justify-end gap-8">
				<button class="px-16 py-8 border border-gray-200 rounded-md text-sm">Abbrechen</button>
				<button class="px-16 py-8 bg-accent text-white rounded-md text-sm">Speichern</button>
			</div>
		</section>

		<section v-else-if="tab === 'notes'" class="space-y-16">
			<div class="bg-white border border-gray-200 rounded-md p-16 space-y-12">
				<textarea
					placeholder="Neue Notiz …"
					rows="3"
					class="block w-full border border-gray-200 rounded-md px-12 py-8 text-sm"
				></textarea>
				<div class="flex justify-end">
					<button class="px-16 py-8 bg-accent text-white rounded-md text-sm">Hinzufügen</button>
				</div>
			</div>
			<div class="bg-white border border-gray-200 rounded-md p-24 text-sm text-gray-500 text-center">
				Keine Notizen vorhanden.
			</div>
		</section>

		<section v-else-if="tab === 'history'" class="space-y-32">
			<div>
				<h2 class="text-sm font-semibold mb-8">Statusverlauf</h2>
				<div class="bg-white border border-gray-200 rounded-md p-24 text-sm text-gray-500 text-center">
					Keine Statusereignisse.
				</div>
			</div>
			<div>
				<h2 class="text-sm font-semibold mb-8">Änderungsverlauf</h2>
				<div class="bg-white border border-gray-200 rounded-md p-24 text-sm text-gray-500 text-center">
					Keine Änderungen erfasst.
				</div>
			</div>
		</section>
	</div>
</template>
