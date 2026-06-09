<script setup>
defineProps({
	for: { type: String, default: null },
	error: { type: [String, Array], default: null },
})
</script>

<template>
	<label :for="$props.for">
		{{ error ? (Array.isArray(error) ? error[0] : error) : undefined }}<slot v-if="!error" />
	</label>
</template>
