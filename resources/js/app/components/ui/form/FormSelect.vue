<script setup>
const model = defineModel()

defineProps({
	id: { type: String, default: null },
	options: { type: Array, default: () => [] }, // [{ value, label }]
	placeholder: { type: String, default: '– Auswählen –' },
	disabled: { type: Boolean, default: false },
	hasError: { type: Boolean, default: false },
})
</script>

<template>
	<div class="relative">
		<select
			:id="id"
			v-model="model"
			:disabled="disabled"
			class="block w-full px-12 py-10 pr-32 border rounded-md appearance-none bg-white dark:bg-warm-800 text-sm dark:text-warm-100 transition-all focus:outline-none focus:ring-2 disabled:opacity-40 disabled:cursor-not-allowed"
			:class="hasError
				? 'border-red-400 dark:border-red-500 focus:ring-red-200 dark:focus:ring-red-800 focus:border-red-400 dark:focus:border-red-500'
				: 'border-gray-200 dark:border-warm-700 focus:ring-gray-200 dark:focus:ring-warm-700 focus:border-gray-300 dark:focus:border-warm-600'"
		>
			<option v-if="placeholder" :value="null">{{ placeholder }}</option>
			<option
				v-for="option in options"
				:key="option.value"
				:value="option.value"
			>
				{{ option.label }}
			</option>
		</select>
		<svg
			class="absolute right-10 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400 dark:text-warm-400"
			width="12" height="12" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"
		>
			<polyline points="2 4 6 8 10 4" />
		</svg>
	</div>
</template>
