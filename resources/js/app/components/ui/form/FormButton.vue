<script setup>
defineProps({
	type: { type: String, default: 'button' },
	variant: { type: String, default: 'primary' },
})
</script>

<template>
	<button
		:type="type"
		class="text-sm inline-flex items-center justify-center gap-8 rounded-md cursor-pointer transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-200 disabled:opacity-50 disabled:cursor-not-allowed px-16 py-8"
		:class="{
			'bg-gray-900 text-white hover:bg-gray-800': variant === 'primary',
			'bg-gray-100 text-gray-700 hover:bg-gray-200': variant === 'secondary',
			'bg-red-600 text-white hover:bg-red-700': variant === 'destructive',
		}"
	>
		<slot />
	</button>
</template>
