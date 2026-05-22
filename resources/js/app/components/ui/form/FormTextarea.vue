<script setup>
const model = defineModel()

defineProps({
	placeholder: { type: String, default: null },
	id: { type: String, default: null },
	rows: { type: [String, Number], default: 3 },
	hasError: { type: Boolean, default: false },
})
</script>

<template>
	<textarea
		:id="id"
		:rows="rows"
		:placeholder="placeholder"
		v-model="model"
		class="block w-full px-12 py-10 border rounded-md bg-white dark:bg-warm-800 text-sm dark:text-warm-100 dark:placeholder:text-warm-600 transition-all focus:outline-none focus:ring-2 resize-none"
		:class="hasError
			? 'border-red-400 dark:border-red-500 focus:ring-red-200 dark:focus:ring-red-800 focus:border-red-400 dark:focus:border-red-500'
			: 'border-gray-200 dark:border-warm-700 focus:ring-gray-200 dark:focus:ring-warm-700 focus:border-gray-300 dark:focus:border-warm-600'"
	/>
</template>
