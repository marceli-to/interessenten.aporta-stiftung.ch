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
	last: 'pl-15 pr-5 text-right',
}
</script>

<template>
	<th class="pb-20 font-medium" :class="padding[variant] ?? 'px-15'">
		<slot />
	</th>
</template>
