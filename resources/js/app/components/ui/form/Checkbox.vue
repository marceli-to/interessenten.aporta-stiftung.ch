<script setup>
const model = defineModel()

defineProps({
	id: { type: String, default: null },
})
</script>

<template>
	<label>
		<input
			:id="id"
			type="checkbox"
			v-model="model"
		/>
		<span><slot /></span>
	</label>
</template>
