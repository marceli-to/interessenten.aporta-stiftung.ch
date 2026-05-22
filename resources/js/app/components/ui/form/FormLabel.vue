<script setup>
defineProps({
	for: { type: String, default: null },
	error: { type: [String, Array], default: null },
})
</script>

<template>
	<label
		:for="$props.for"
		class="block text-xs mb-8"
		:class="error ? 'text-red-500 dark:text-red-400' : 'text-gray-500 dark:text-warm-400'"
	>
		{{ error ? (Array.isArray(error) ? error[0] : error) : undefined }}<slot v-if="!error" />
	</label>
</template>
