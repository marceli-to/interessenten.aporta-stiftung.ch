<script setup>
</script>

<template>
	<div class="space-y-24">
		<div class="flex items-center justify-between">
			<h1 class="text-xl font-semibold">Bewerbungen</h1>
		</div>

		<div class="bg-white border border-gray-200 rounded-md p-16 flex flex-wrap items-end gap-16">
			<div class="flex flex-col gap-4">
				<label class="text-xs text-gray-500">Status</label>
				<select class="border border-gray-200 rounded-md px-12 py-8 text-sm">
					<option>Alle</option>
				</select>
			</div>
			<div class="flex flex-col gap-4">
				<label class="text-xs text-gray-500">Bezirk</label>
				<select class="border border-gray-200 rounded-md px-12 py-8 text-sm">
					<option>Alle</option>
				</select>
			</div>
			<div class="flex flex-col gap-4">
				<label class="text-xs text-gray-500">Zuständig</label>
				<select class="border border-gray-200 rounded-md px-12 py-8 text-sm">
					<option>Alle</option>
				</select>
			</div>
			<label class="flex items-center gap-8 text-sm">
				<input type="checkbox" />
				Markiert
			</label>
			<label class="flex items-center gap-8 text-sm">
				<input type="checkbox" />
				Meine
			</label>
			<div class="flex flex-col gap-4 ml-auto">
				<label class="text-xs text-gray-500">Suche</label>
				<input type="search" placeholder="Name, Referenz …" class="border border-gray-200 rounded-md px-12 py-8 text-sm" />
			</div>
		</div>

		<div class="bg-white border border-gray-200 rounded-md overflow-hidden">
			<table class="w-full text-sm">
				<thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
					<tr>
						<th class="px-16 py-12">Referenz</th>
						<th class="px-16 py-12">Name</th>
						<th class="px-16 py-12">Bezirk</th>
						<th class="px-16 py-12">Status</th>
						<th class="px-16 py-12">Zuständig</th>
						<th class="px-16 py-12">Eingang</th>
						<th class="px-16 py-12"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<tr v-for="i in 4" :key="i" class="hover:bg-gray-50 cursor-pointer">
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">›</td>
					</tr>
				</tbody>
			</table>
		</div>

		<div class="flex items-center justify-between text-sm text-gray-500">
			<span>0 Einträge</span>
			<div class="flex gap-8">
				<button class="px-12 py-4 border border-gray-200 rounded-md" disabled>Zurück</button>
				<button class="px-12 py-4 border border-gray-200 rounded-md" disabled>Weiter</button>
			</div>
		</div>
	</div>
</template>
