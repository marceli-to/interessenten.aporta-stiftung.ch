<script setup>
import { computed, useSlots } from 'vue'
import Heading2 from '@/components/ui/headings/H2.vue'

const props = defineProps({
	variant: {
		type: String,
		default: 'default',
		validator: (v) => ['default', 'highlight', 'danger'].includes(v),
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
	danger: 'bg-light-red',
}

const hasHeader = computed(() => !!props.title || !!slots.action)
</script>

<template>

	<div class="rounded-2xl shadow-xl p-20" :class="variants[variant] ?? variants.default">
    
		<div v-if="hasHeader" class="flex items-start mb-15">
			<Heading2 v-if="title" :class="{ 'text-red!': variant === 'danger' }">
				{{ title }}
			</Heading2>
			<div v-if="slots.action" class="ml-auto">
				<slot name="action" />
			</div>
		</div>

		<slot />

	</div>

</template>
