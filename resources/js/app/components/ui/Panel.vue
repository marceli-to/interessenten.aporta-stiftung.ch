<script setup>
import { computed, useSlots } from 'vue'

const props = defineProps({
	variant: {
		type: String,
		default: 'default',
		validator: (v) => ['default', 'highlight'].includes(v),
	},
	title: {
		type: String,
		default: null,
	},
	padding: {
		type: String,
		default: null,
	},
})

const slots = useSlots()

const variants = {
	default: 'bg-white',
	highlight: 'bg-yellow',
}

const hasHeader = computed(() => !!props.title || !!slots.action)
</script>

<template>
	<div
		class="rounded-[15px] shadow-[0px_6px_15px_3px_rgba(0,0,0,0.10)]"
		:class="[variants[variant] ?? variants.default, padding]"
	>
		<div v-if="hasHeader" class="flex items-center pb-15 mb-15 border-b border-black/10">
			<h2 v-if="title" class="text-xl font-bold leading-none text-blue">
				{{ title }}
			</h2>
			<div v-if="slots.action" class="ml-auto">
				<slot name="action" />
			</div>
		</div>

		<slot />
	</div>
</template>
