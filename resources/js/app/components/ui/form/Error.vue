<script setup>
defineProps({
	message: { type: [String, Array], default: null },
})
</script>

<template>
	<p v-if="message">
		{{ Array.isArray(message) ? message[0] : message }}
	</p>
</template>
