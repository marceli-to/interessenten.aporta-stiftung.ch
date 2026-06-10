<script setup>
defineProps({
	for: { type: String, default: null },
	error: { type: [String, Array], default: null },
})
</script>

<template>
	<label :for="$props.for" class="block text-sm font-bold text-blue mb-10">
		{{ error ? (Array.isArray(error) ? error[0] : error) : undefined }}<slot v-if="!error" />
	</label>
</template>
