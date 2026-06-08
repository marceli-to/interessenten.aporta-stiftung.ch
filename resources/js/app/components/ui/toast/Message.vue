<script setup>
import { PhCheckCircle, PhWarningCircle, PhWarning, PhInfo, PhX } from '@phosphor-icons/vue'

defineProps({
	toast: { type: Object, required: true },
})
defineEmits(['close'])

// type → colour + icon, kept as lookup maps so the template stays declarative.
const styles = {
	success: 'bg-emerald-600 text-white',
	error: 'bg-red-600 text-white',
	warning: 'bg-amber-500 text-white',
	info: 'bg-gray-800 text-white',
}
const icons = {
	success: PhCheckCircle,
	error: PhWarningCircle,
	warning: PhWarning,
	info: PhInfo,
}
</script>

<template>
	<div
		class="flex items-center gap-10 text-sm py-12 px-16 rounded-md shadow-md cursor-pointer"
		:class="styles[toast.type]"
		@click="$emit('close')"
	>
		<component :is="icons[toast.type]" :size="18" weight="regular" class="shrink-0" />
		<span class="flex-1">{{ toast.message }}</span>
		<PhX :size="14" weight="bold" class="shrink-0 opacity-60 hover:opacity-100" />
	</div>
</template>
