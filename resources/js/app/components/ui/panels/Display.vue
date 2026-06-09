<script setup>
import { computed, useSlots } from 'vue'
import Heading2 from '@/components/ui/headings/H2.vue'

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
})

const slots = useSlots()

const variants = {
	default: 'bg-white',
	highlight: 'bg-yellow',
}

const hasHeader = computed(() => !!props.title || !!slots.action)
</script>

<template>

	<div class="rounded-2xl shadow-xl px-20 py-25"	:class="variants[variant] ?? variants.default">
    
		<div v-if="hasHeader" class="flex items-center mb-15">
			<Heading2 v-if="title">
				{{ title }}
			</Heading2>
			<div v-if="slots.action" class="ml-auto">
				<slot name="action" />
			</div>
		</div>

		<slot />

	</div>

</template>
