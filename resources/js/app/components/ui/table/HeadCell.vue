<script setup>
import { computed } from 'vue'
import { PhArrowsDownUp } from '@phosphor-icons/vue'

const props = defineProps({
	variant: {
		type: String,
		default: null,
		validator: (v) => v === null || ['first', 'last'].includes(v),
	},
	// When set, the header becomes a sort toggle for this column key.
	sortKey: { type: String, default: null },
	// The currently active sort column / direction, so the header can render
	// its indicator and decide which way the next click sorts.
	sort: { type: String, default: null },
	direction: { type: String, default: 'desc' },
})

const emit = defineEmits(['sort'])

const padding = {
	first: 'pr-15 pl-5',
	last: 'pl-15 pr-5 text-right',
}

const active = computed(() => props.sortKey && props.sort === props.sortKey)
</script>

<template>
	<th class="pb-20 font-medium" :class="padding[variant] ?? 'px-15'">
		<button
			v-if="sortKey"
			type="button"
			class="inline-flex items-center gap-3 cursor-pointer"
			@click="emit('sort', sortKey)"
		>
			<slot />
			<PhArrowsDownUp v-if="active" :size="16" weight="regular" />
		</button>
		<slot v-else />
	</th>
</template>
