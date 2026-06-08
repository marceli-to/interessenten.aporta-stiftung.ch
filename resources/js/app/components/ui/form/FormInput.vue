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
		class="block w-full px-10 py-10 rounded-xs text-sm transition-all focus:outline-none focus:ring-none"
		:class="hasError
			? 'bg-light-red/20'
			: 'bg-light-blue/40'"
	/>
</template>
