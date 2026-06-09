<script setup>
// Round button shared by the prev/next arrows and the page numbers.
// `disabled` (arrows at the ends) and `active` (current page) are mutually
// exclusive; everything else gets the default hover treatment.
defineProps({
	active: { type: Boolean, default: false },
	disabled: { type: Boolean, default: false },
})

defineEmits(['click'])
</script>

<template>
	<button
		type="button"
		class="flex items-center justify-center w-35 h-35 rounded-full transition-colors cursor-pointer"
		:class="disabled
			? 'text-light-gray cursor-not-allowed'
			: active
				? 'bg-blue text-white'
				: 'text-blue hover:bg-blue hover:text-white'"
		:disabled="disabled"
		@click="$emit('click')">
		<slot />
	</button>
</template>
