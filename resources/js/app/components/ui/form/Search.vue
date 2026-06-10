<script setup>
import { PhMagnifyingGlass, PhX } from '@phosphor-icons/vue'

const model = defineModel({ type: String, default: '' })

defineProps({
	placeholder: { type: String, default: 'Suchen …' },
})
</script>

<template>
	<div class="relative w-full max-w-[400px]">
		<PhMagnifyingGlass :size="18" weight="regular" class="absolute left-12 top-1/2 -translate-y-1/2 text-blue" />
		<input
			v-model="model"
			type="text"
			:placeholder="placeholder"
			class="w-full pl-40 pr-40 py-8 text-md border-none! bg-white rounded-full text-gray focus:outline-none focus:border-blue"
		>
		<button
			v-if="model"
			type="button"
			class="absolute right-12 top-1/2 -translate-y-1/2 text-light-gray hover:text-blue cursor-pointer"
			@click="model = ''"
		>
			<PhX :size="18" weight="regular" />
		</button>
	</div>
</template>
