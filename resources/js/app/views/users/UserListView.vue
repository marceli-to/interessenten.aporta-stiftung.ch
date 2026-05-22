<script setup>
</script>

<template>
	<div class="space-y-24">
		<div class="flex items-center justify-between">
			<h1 class="text-xl font-semibold">Benutzer</h1>
			<button class="px-16 py-8 bg-accent text-white rounded-md text-sm">Neuer Benutzer</button>
		</div>

		<div class="bg-white border border-gray-200 rounded-md overflow-hidden">
			<table class="w-full text-sm">
				<thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
					<tr>
						<th class="px-16 py-12">Name</th>
						<th class="px-16 py-12">E-Mail</th>
						<th class="px-16 py-12">Rolle</th>
						<th class="px-16 py-12"></th>
					</tr>
				</thead>
				<tbody class="divide-y divide-gray-100">
					<tr v-for="i in 3" :key="i" class="hover:bg-gray-50 cursor-pointer">
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">—</td>
						<td class="px-16 py-12 text-gray-400">›</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
</template>
