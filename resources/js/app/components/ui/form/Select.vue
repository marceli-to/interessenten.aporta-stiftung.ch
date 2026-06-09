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
	<select
		:id="id"
		v-model="model"
		:disabled="disabled"
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
</template>
