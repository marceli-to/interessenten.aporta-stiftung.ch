<script setup>
import { computed } from 'vue'
import { PhCaretLeft, PhCaretRight } from '@phosphor-icons/vue'
import { useBrowseStore } from '@/stores/browse'
import PaginationButton from '@/components/ui/pagination/Button.vue'

// Prev/next browse control for the detail view (Resultatansicht). Renders only
// while the current application is part of the browse set the user opened from
// the list. Owns the browse-store derivation (position, neighbours); the parent
// handles the actual navigation (it knows the back-link state) via @navigate.
const props = defineProps({
	id: { type: [String, Number], required: true },
})

const emit = defineEmits(['navigate'])

const browse = useBrowseStore()

const inSet = computed(() => browse.active && browse.has(props.id))
const position = computed(() => browse.position(props.id))
const prevId = computed(() => browse.prevId(props.id))
const nextId = computed(() => browse.nextId(props.id))

function go(id) {
	if (id != null) emit('navigate', id)
}
</script>

<template>
	<div v-if="inSet" class="flex items-center justify-center gap-10">
		<PaginationButton
			:disabled="prevId === null"
			aria-label="Vorherige Bewerbung"
			@click="go(prevId)"
		>
			<PhCaretLeft :size="16" weight="regular" />
		</PaginationButton>
		<span class="text-sm tabular-nums text-blue whitespace-nowrap">{{ position }} / {{ browse.total }}</span>
		<PaginationButton
			:disabled="nextId === null"
			aria-label="Nächste Bewerbung"
			@click="go(nextId)"
		>
			<PhCaretRight :size="16" weight="regular" />
		</PaginationButton>
	</div>
</template>
