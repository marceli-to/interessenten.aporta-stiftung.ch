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
	/>
</template>
