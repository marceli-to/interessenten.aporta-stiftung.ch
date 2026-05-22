<script setup>
defineProps({
	message: { type: [String, Array], default: null },
})
</script>

<template>
	<p v-if="message" class="mt-8 text-[11px] text-red-600">
		{{ Array.isArray(message) ? message[0] : message }}
	</p>
</template>
