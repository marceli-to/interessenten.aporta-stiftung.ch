<script setup>
import { computed } from 'vue'
import { PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue'

const props = defineProps({
	page: { type: Number, required: true },
	lastPage: { type: Number, required: true },
	total: { type: Number, required: true },
	from: { type: Number, default: 0 },
	to: { type: Number, default: 0 },
})

const emit = defineEmits(['change'])

// Page buttons to render. Up to 7 pages show in full; beyond that we collapse
// the gaps around the current page into ellipses ('…').
const items = computed(() => {
	const last = props.lastPage

	if (last <= 7) {
		return Array.from({ length: last }, (_, i) => i + 1)
	}

	const shown = [1, last, props.page, props.page - 1, props.page + 1]
		.filter((p) => p >= 1 && p <= last)
		.sort((a, b) => a - b)

	const result = []
	let prev = 0
	for (const p of [...new Set(shown)]) {
		if (p - prev > 1) result.push('…')
		result.push(p)
		prev = p
	}
	return result
})

function go(page) {
	if (page < 1 || page > props.lastPage || page === props.page) return
	emit('change', page)
}
</script>

<template>
	<div class="flex items-center justify-between text-sm text-blue px-20">
		<div>
			{{ from }} – {{ to }} von {{ total }}
		</div>

		<div v-if="lastPage > 1" class="flex items-center gap-5">
			<button
				type="button"
				class="flex items-center justify-center w-35 h-35 rounded-full transition-colors cursor-pointer"
				:class="page <= 1	? 'text-light-gray cursor-not-allowed' : 'text-blue hover:bg-blue hover:text-white'" 
        :disabled="page <= 1"
        @click="go(page - 1)">
				<PhCaretLeft :size="16" weight="bold" />
			</button>

			<template v-for="(item, i) in items" :key="i">
				<span v-if="item === '…'"	class="flex items-center justify-center w-35 h-35 text-light-gray">
					…
				</span>
				<button
					v-else
					type="button"
					class="flex items-center justify-center w-35 h-35 rounded-full font-medium transition-colors cursor-pointer"
					:class="item === page ? 'bg-blue text-white': 'text-blue hover:bg-blue hover:text-white'"
					@click="go(item)">
					{{ item }}
				</button>
			</template>

			<button
				type="button"
				class="flex items-center justify-center w-35 h-35 rounded-full transition-colors cursor-pointer"
				:class="page >= lastPage? 'text-light-gray cursor-not-allowed' : 'text-blue hover:bg-blue hover:text-white'"
				:disabled="page >= lastPage"
				@click="go(page + 1)">
				<PhCaretRight :size="16" weight="bold" />
			</button>
		</div>
	</div>
</template>
