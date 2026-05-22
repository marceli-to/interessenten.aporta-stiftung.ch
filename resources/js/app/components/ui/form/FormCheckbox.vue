<script setup>
const model = defineModel()

defineProps({
	id: { type: String, default: null },
})
</script>

<template>
	<label class="inline-flex items-center gap-10 cursor-pointer group">
		<span class="relative flex items-center justify-center size-14">
			<input
				:id="id"
				type="checkbox"
				v-model="model"
				class="peer appearance-none size-14 border border-gray-200 dark:border-warm-600 rounded bg-white dark:bg-warm-800 checked:bg-gray-900 dark:checked:bg-warm-100 checked:border-gray-900 dark:checked:border-warm-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-200 dark:focus-visible:ring-warm-700 focus-visible:ring-offset-0 cursor-pointer transition-colors"
			/>
			<svg
				class="absolute pointer-events-none text-white dark:text-warm-900 opacity-0 peer-checked:opacity-100 transition-opacity"
				width="10" height="10" viewBox="0 0 10 10" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
			>
				<polyline points="1.5 5.5 4 8 8.5 2.5" />
			</svg>
		</span>
		<span class="text-sm text-gray-500 dark:text-warm-400 group-hover:text-gray-900 dark:group-hover:text-warm-100 transition-colors select-none">
			<slot />
		</span>
	</label>
</template>
