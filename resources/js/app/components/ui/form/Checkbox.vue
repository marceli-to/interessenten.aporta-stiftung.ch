<script setup>
const model = defineModel()

defineProps({
	id: { type: String, default: null },
	hasError: { type: Boolean, default: false },
})
</script>

<template>
	<label>
		<input
			:id="id"
			type="checkbox"
			v-model="model"
			:class="{ 'bg-light-red/30': hasError }"
		/>
		<span><slot /></span>
	</label>
</template>
