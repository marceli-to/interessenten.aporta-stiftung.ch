<script setup>
defineProps({
	variant: {
		type: String,
		default: null,
		validator: (v) => v === null || ['first', 'last'].includes(v),
	},
})

const padding = {
	first: 'pr-15 pl-5',
	last: 'pr-15',
}
</script>

<template>
	<th class="pb-20" :class="padding[variant] ?? 'px-15'">
		<slot />
	</th>
</template>
