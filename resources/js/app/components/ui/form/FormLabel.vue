<script setup>
defineProps({
	for: { type: String, default: null },
	error: { type: [String, Array], default: null },
})
</script>

<template>
	<label
		:for="$props.for"
		class="block font-bold text-sm mb-10"
		:class="error ? 'text-red' : 'text-blue'"
	>
		{{ error ? (Array.isArray(error) ? error[0] : error) : undefined }}<slot v-if="!error" />
	</label>
</template>
