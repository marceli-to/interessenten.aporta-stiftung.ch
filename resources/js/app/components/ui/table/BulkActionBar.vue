<script setup>
import Button from '@/components/ui/form/Button.vue'

// Floating action bar for list multi-select. Pinned bottom-center, it appears
// (with a short slide/fade) only while at least one row is selected and follows
// the page as it scrolls. The parent owns the selection set and the actions; we
// only render the count, the actions, and a "clear" affordance.
//
// Dark-blue pill surface (kept for visibility over the table). Actions use the
// canonical Button with the on-dark variants — inverse-outline for the neutral
// actions, inverse-ghost for the text affordances, danger-solid for the
// destructive Löschen (the red fill reads fine on dark blue).
//
// Selection is filter-scoped: `count` is how many are selected, `total` is the
// size of the current filtered result. When the whole page is ticked but more
// rows match (`canSelectAll`), we offer the "Alle N auswählen" upgrade, which
// switches the parent into all-matching mode (`allMatching`).
defineProps({
	count: { type: Number, required: true },
	total: { type: Number, default: 0 },
	canSelectAll: { type: Boolean, default: false },
	allMatching: { type: Boolean, default: false },
})

defineEmits(['selectAll', 'clear', 'open', 'export', 'delete'])
</script>

<template>
	<Transition
		enter-active-class="transition duration-200 ease-out"
		enter-from-class="opacity-0 translate-y-15"
		enter-to-class="opacity-100 translate-y-0"
		leave-active-class="transition duration-150 ease-in"
		leave-from-class="opacity-100 translate-y-0"
		leave-to-class="opacity-0 translate-y-15"
	>
		<div
			v-if="count > 0"
			class="fixed inset-x-0 bottom-30 z-40 flex justify-center px-20 pointer-events-none"
		>
			<div
				class="pointer-events-auto flex items-center gap-20 rounded-full bg-blue py-10 pl-25 pr-15 text-white shadow-xl"
			>
				<span class="text-sm whitespace-nowrap">
					<template v-if="allMatching && count === total">
						<span class="font-bold tabular-nums">Alle {{ total }}</span>
						ausgewählt
					</template>
					<template v-else>
						<span class="font-bold tabular-nums">{{ count }}</span>
						von {{ total }} ausgewählt
					</template>
				</span>

				<Button v-if="canSelectAll" variant="inverse-ghost" size="sm" @click="$emit('selectAll')">
					Alle {{ total }} auswählen
				</Button>

				<Button variant="inverse-ghost" size="sm" @click="$emit('clear')">
					Abwählen
				</Button>

				<div class="h-20 w-px bg-white/20" />

				<div class="flex items-center gap-10">
					<Button variant="inverse-outline" size="sm" icon="arrow-square-out" @click="$emit('open')">
						Öffnen
					</Button>
					<Button variant="inverse-outline" size="sm" icon="download-simple" @click="$emit('export')">
						Exportieren
					</Button>
					<Button variant="danger-solid" size="sm" icon="trash" @click="$emit('delete')">
						Löschen
					</Button>
				</div>
			</div>
		</div>
	</Transition>
</template>
