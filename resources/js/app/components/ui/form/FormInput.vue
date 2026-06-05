<script setup>
const model = defineModel()

defineProps({
	type: { type: String, default: 'text' },
	placeholder: { type: String, default: null },
	id: { type: String, default: null },
	hasError: { type: Boolean, default: false },
})
</script>

<template>
	<input
		:id="id"
		:type="type"
		:placeholder="placeholder"
		v-model="model"
		class="block w-full px-12 py-10 border rounded-md bg-white text-sm transition-all focus:outline-none focus:ring-2"
		:class="hasError
			? 'border-red-400 focus:ring-red-200 focus:border-red-400'
			: 'border-gray-200 focus:ring-gray-200 focus:border-gray-300'"
	/>
</template>
