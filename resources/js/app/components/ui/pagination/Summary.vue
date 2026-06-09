<script setup>
defineProps({
	total: { type: Number, required: true },
	from: { type: Number, default: 0 },
	to: { type: Number, default: 0 },
})
</script>

<template>
	<div>
		{{ from }} – {{ to }} von {{ total }}
	</div>
</template>
