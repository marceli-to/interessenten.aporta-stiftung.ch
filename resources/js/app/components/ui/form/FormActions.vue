<script setup>
defineProps({
	submitLabel: { type: String, default: null },
	cancelLabel: { type: String, default: null },
})

const emit = defineEmits(['cancel'])
</script>

<template>
	<div class="fixed top-16 right-16 z-20 flex items-center gap-12 p-24">
		<slot>
			<button
				v-if="cancelLabel"
				type="button"
				class="text-sm px-16 py-8 rounded-md bg-gray-100 dark:bg-warm-800 text-gray-700 dark:text-warm-300 hover:bg-gray-200 dark:hover:bg-warm-700 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-200 dark:focus-visible:ring-warm-700"
				@click="emit('cancel')"
			>
				{{ cancelLabel }}
			</button>
			<button
				v-if="submitLabel"
				type="submit"
				class="text-sm px-16 py-8 rounded-md bg-gray-900 dark:bg-warm-100 text-white dark:text-warm-900 hover:bg-gray-800 dark:hover:bg-warm-200 transition-colors cursor-pointer focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-200 dark:focus-visible:ring-warm-700"
			>
				{{ submitLabel }}
			</button>
		</slot>
	</div>
</template>
