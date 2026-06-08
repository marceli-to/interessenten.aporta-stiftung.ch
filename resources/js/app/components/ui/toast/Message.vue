<script setup>
import { PhCheckCircle, PhWarningCircle, PhWarning, PhInfo, PhX } from '@phosphor-icons/vue'

defineProps({
	toast: { type: Object, required: true },
})
defineEmits(['close'])

// type → colour + icon, kept as lookup maps so the template stays declarative.
const styles = {
	success: 'bg-green text-white',
	error: 'bg-red text-white',
	warning: 'bg-yellow text-white',
	info: 'bg-blue text-white',
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
		class="flex items-center gap-10 text-sm py-10 px-15 rounded-xs shadow-md cursor-pointer"
		:class="styles[toast.type]"
		@click="$emit('close')"
	>
		<component :is="icons[toast.type]" :size="18" weight="regular" class="shrink-0" />
		<span class="flex-1">{{ toast.message }}</span>
		<PhX :size="14" weight="bold" class="shrink-0 opacity-60 hover:opacity-100" />
	</div>
</template>
