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
	/>
</template>
