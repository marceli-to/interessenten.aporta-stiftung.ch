<script setup>
import { RouterLink } from 'vue-router'

defineProps({
	to: { type: [String, Object], required: true },
})
</script>

<template>
	<RouterLink :to="to" active-class="border-b-2 border-accent" class="py-4">
		<slot />
	</RouterLink>
</template>
