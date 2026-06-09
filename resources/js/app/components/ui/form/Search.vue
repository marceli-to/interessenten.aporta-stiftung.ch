<script setup>
import { PhMagnifyingGlass, PhX } from '@phosphor-icons/vue'

const model = defineModel({ type: String, default: '' })

defineProps({
	placeholder: { type: String, default: 'Suchen …' },
})
</script>

<template>
	<div class="relative max-w-[320px]">
		<PhMagnifyingGlass :size="16" weight="regular" class="absolute left-12 top-1/2 -translate-y-1/2 text-light-gray" />
		<input
			v-model="model"
			type="text"
			:placeholder="placeholder"
			class="w-full pl-32 pr-32 py-8 text-sm border border-blue/20 rounded-full text-gray focus:outline-none focus:border-blue"
		>
		<button
			v-if="model"
			type="button"
			class="absolute right-10 top-1/2 -translate-y-1/2 text-light-gray hover:text-blue cursor-pointer"
			@click="model = ''"
		>
			<PhX :size="16" weight="regular" />
		</button>
	</div>
</template>
